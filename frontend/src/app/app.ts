import { CommonModule } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Category, Dashboard, InventoryApi, Item, Purchase, Sale, Supplier } from './inventory-api';

type View = 'dashboard' | 'items' | 'purchases' | 'sales' | 'categories' | 'suppliers';
type Editor = 'item' | 'category' | 'supplier' | null;

interface Draft {
  name: string;
  phone: string;
  email: string;
  category_id: number | null;
  price: number;
  quantity: number;
}

interface SaleDraftLine {
  item_id: number | null;
  quantity: number;
  price: number;
}

@Component({
  selector: 'app-root',
  imports: [CommonModule, FormsModule],
  templateUrl: './inventory.html',
  styleUrl: './app.css'
})
export class App {
  private readonly api = inject(InventoryApi);
  readonly view = signal<View>('dashboard');
  readonly dashboard = signal<Dashboard | null>(null);
  readonly categories = signal<Category[]>([]);
  readonly suppliers = signal<Supplier[]>([]);
  readonly items = signal<Item[]>([]);
  readonly purchases = signal<Purchase[]>([]);
  readonly sales = signal<Sale[]>([]);
  readonly search = signal('');
  readonly categoryFilter = signal('');
  readonly editor = signal<Editor>(null);
  readonly editingId = signal<number | null>(null);
  readonly transaction = signal<'purchase' | 'sale' | null>(null);
  readonly supplierId = signal<number | null>(null);
  readonly transactionDate = signal(new Date().toISOString().slice(0, 10));
  readonly lines = signal<SaleDraftLine[]>([{ item_id: null, quantity: 1, price: 0 }]);
  readonly busy = signal(false);
  readonly notice = signal('');
  readonly error = signal('');
  readonly today = new Date();
  readonly total = computed(() => this.lines().reduce((sum, line) => sum + line.quantity * line.price, 0));
  draft: Draft = this.emptyDraft();

  constructor() {
    void this.loadDashboard();
    void this.loadCatalogs();
  }

  title(): string {
    const titles: Record<View, string> = {
      dashboard: 'Overview', items: 'Items', purchases: 'Purchases',
      sales: 'Sales', categories: 'Categories', suppliers: 'Suppliers'
    };
    return titles[this.view()];
  }

  async navigate(view: View): Promise<void> {
    this.view.set(view);
    this.clearMessages();
    if (view === 'dashboard') await this.loadDashboard();
    if (view === 'items') await this.loadItems();
    if (view === 'purchases') await this.loadPurchases();
    if (view === 'sales') await this.loadSales();
    if (view === 'purchases' || view === 'sales') await this.loadItems();
    if (view === 'categories' || view === 'suppliers') await this.loadCatalogs();
  }

  async loadCatalogs(): Promise<void> {
    try {
      const [categories, suppliers] = await Promise.all([this.api.categories(), this.api.suppliers()]);
      this.categories.set(categories);
      this.suppliers.set(suppliers);
    } catch (error) {
      this.showError(error);
    }
  }

  async loadDashboard(): Promise<void> {
    try {
      this.dashboard.set(await this.api.dashboard());
    } catch (error) {
      this.showError(error);
    }
  }

  async loadItems(): Promise<void> {
    try {
      this.items.set(await this.api.items(this.search(), this.categoryFilter()));
    } catch (error) {
      this.showError(error);
    }
  }

  async loadPurchases(): Promise<void> {
    try {
      this.purchases.set(await this.api.purchases());
    } catch (error) {
      this.showError(error);
    }
  }

  async loadSales(): Promise<void> {
    try {
      this.sales.set(await this.api.sales());
    } catch (error) {
      this.showError(error);
    }
  }

  openEditor(kind: Exclude<Editor, null>, record?: Item | Category | Supplier): void {
    this.editor.set(kind);
    this.editingId.set(record?.id ?? null);
    this.draft = this.emptyDraft();
    if (kind === 'item' && record && 'category_id' in record) {
      this.draft = { ...this.draft, name: record.name, category_id: record.category_id, price: Number(record.price), quantity: record.quantity };
    } else if (kind === 'category' && record) {
      this.draft.name = record.name;
    } else if (kind === 'supplier' && record && 'email' in record) {
      this.draft = { ...this.draft, name: record.name, phone: record.phone ?? '', email: record.email ?? '' };
    }
    this.clearMessages();
  }

  closeEditor(): void {
    this.editor.set(null);
    this.editingId.set(null);
  }

  handleCategorySelect(value: number | string): void {
    if (value === '__new__') {
      this.editor.set('category');
      this.editingId.set(null);
      this.draft = this.emptyDraft();
      this.clearMessages();
      return;
    }

    this.draft.category_id = Number(value);
  }

  async saveEditor(): Promise<void> {
    this.busy.set(true);
    this.clearMessages();
    try {
      const id = this.editingId() ?? undefined;
      if (this.editor() === 'category') {
        await this.api.saveCategory({ name: this.draft.name }, id);
      } else if (this.editor() === 'supplier') {
        await this.api.saveSupplier({ name: this.draft.name, phone: this.draft.phone, email: this.draft.email }, id);
      } else if (this.editor() === 'item') {
        await this.api.saveItem({
          category_id: Number(this.draft.category_id), name: this.draft.name,
          price: Number(this.draft.price), quantity: Number(this.draft.quantity)
        }, id);
      }
      this.closeEditor();
      await this.refreshCurrent();
      this.notice.set('Changes saved.');
    } catch (error) {
      this.showError(error);
    } finally {
      this.busy.set(false);
    }
  }

  async remove(kind: 'categories' | 'suppliers' | 'items', record: Category | Supplier | Item): Promise<void> {
    if (!window.confirm(`Delete “${record.name}”?`)) return;
    try {
      await this.api.remove(kind, record.id);
      await this.refreshCurrent();
      this.notice.set('Record deleted.');
    } catch (error) {
      this.showError(error);
    }
  }

  startTransaction(kind: 'purchase' | 'sale'): void {
    this.transaction.set(kind);
    void this.loadItems();
    void this.loadCatalogs();
    this.supplierId.set(null);
    this.transactionDate.set(new Date().toISOString().slice(0, 10));
    this.lines.set([{ item_id: null, quantity: 1, price: 0 }]);
    this.clearMessages();
  }

  cancelTransaction(): void {
    this.transaction.set(null);
  }

  addLine(): void {
    this.lines.update(lines => [...lines, { item_id: null, quantity: 1, price: 0 }]);
  }

  removeLine(index: number): void {
    this.lines.update(lines => lines.length > 1 ? lines.filter((_, lineIndex) => lineIndex !== index) : lines);
  }

  updateLine(index: number, field: 'quantity' | 'price', value: number): void {
    this.lines.update(lines => lines.map((line, lineIndex) => lineIndex === index
      ? { ...line, [field]: Number(value) }
      : line));
  }

  setLineItem(index: number, rawId: string): void {
    const item = this.items().find(candidate => candidate.id === Number(rawId));
    this.lines.update(lines => lines.map((line, lineIndex) => lineIndex === index
      ? { ...line, item_id: item?.id ?? null, price: item ? Number(item.price) : 0 }
      : line));
  }

  available(itemId: number | null): number {
    return this.items().find(item => item.id === itemId)?.quantity ?? 0;
  }

  canSaveTransaction(): boolean {
    const kind = this.transaction();
    if (!kind || (kind === 'purchase' && !this.supplierId())) return false;
    return this.lines().every(line => line.item_id !== null && line.quantity > 0 && line.price >= 0
      && (kind !== 'sale' || line.quantity <= this.available(line.item_id)));
  }

  async saveTransaction(): Promise<void> {
    const kind = this.transaction();
    if (!kind) return;
    this.busy.set(true);
    this.clearMessages();
    try {
      const lines = this.lines().map(line => ({ item_id: Number(line.item_id), quantity: Number(line.quantity), price: Number(line.price) }));
      if (kind === 'purchase') {
        await this.api.savePurchase({
          supplier_id: Number(this.supplierId()), purchase_date: this.transactionDate(),
          items: lines.map(line => ({ item_id: line.item_id, quantity: line.quantity, purchase_price: line.price }))
        });
      } else {
        await this.api.saveSale({
          sale_date: this.transactionDate(),
          items: lines.map(line => ({ item_id: line.item_id, quantity: line.quantity, selling_price: line.price }))
        });
      }
      this.transaction.set(null);
      await Promise.all([this.loadItems(), this.loadDashboard(), kind === 'purchase' ? this.loadPurchases() : this.loadSales()]);
      this.notice.set(kind === 'purchase' ? 'Purchase recorded and stock updated.' : 'Sale recorded and stock updated.');
    } catch (error) {
      this.showError(error);
    } finally {
      this.busy.set(false);
    }
  }

  private async refreshCurrent(): Promise<void> {
    if (this.view() === 'items') await this.loadItems();
    if (this.view() === 'categories' || this.view() === 'suppliers') await this.loadCatalogs();
    if (this.view() === 'dashboard') await this.loadDashboard();
  }

  private emptyDraft(): Draft {
    return { name: '', phone: '', email: '', category_id: null, price: 0, quantity: 0 };
  }

  private clearMessages(): void {
    this.notice.set('');
    this.error.set('');
  }

  private showError(error: unknown): void {
    const response = error as { error?: { message?: string; errors?: Record<string, string[]> } };
    const validation = response.error?.errors ? Object.values(response.error.errors)[0]?.[0] : null;
    this.error.set(validation ?? response.error?.message ?? 'Could not reach the inventory API. Check that Laravel is running on port 8000.');
  }
}

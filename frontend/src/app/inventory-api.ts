import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

export interface Category {
  id: number;
  name: string;
  items_count?: number;
}

export interface Supplier {
  id: number;
  name: string;
  phone: string | null;
  email: string | null;
  purchases_count?: number;
}

export interface Item {
  id: number;
  category_id: number;
  name: string;
  price: string;
  quantity: number;
  category: Category;
}

export interface TransactionLine {
  id: number;
  item_id: number;
  quantity: number;
  purchase_price?: number;
  selling_price?: number;
  item?: Item;
}

export interface Purchase {
  id: number;
  supplier: Supplier;
  purchase_date: string;
  total_amount: string;
  items: TransactionLine[];
}

export interface Sale {
  id: number;
  sale_date: string;
  total_amount: string;
  items: TransactionLine[];
}

export interface Dashboard {
  item_count: number;
  category_count: number;
  supplier_count: number;
  stock_units: number;
  low_stock: Item[];
  recent_sales: Sale[];
}

@Injectable({ providedIn: 'root' })
export class InventoryApi {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = 'http://127.0.0.1:8000/api';

  dashboard(): Promise<Dashboard> {
    return firstValueFrom(this.http.get<Dashboard>(`${this.baseUrl}/dashboard`));
  }

  categories(): Promise<Category[]> {
    return firstValueFrom(this.http.get<Category[]>(`${this.baseUrl}/categories`));
  }

  suppliers(): Promise<Supplier[]> {
    return firstValueFrom(this.http.get<Supplier[]>(`${this.baseUrl}/suppliers`));
  }

  items(search = '', categoryId = ''): Promise<Item[]> {
    let params = new HttpParams();
    if (search) params = params.set('search', search);
    if (categoryId) params = params.set('category_id', categoryId);
    return firstValueFrom(this.http.get<Item[]>(`${this.baseUrl}/items`, { params }));
  }

  purchases(): Promise<Purchase[]> {
    return firstValueFrom(this.http.get<Purchase[]>(`${this.baseUrl}/purchases`));
  }

  sales(): Promise<Sale[]> {
    return firstValueFrom(this.http.get<Sale[]>(`${this.baseUrl}/sales`));
  }

  saveCategory(data: { name: string }, id?: number): Promise<Category> {
    return firstValueFrom(id
      ? this.http.put<Category>(`${this.baseUrl}/categories/${id}`, data)
      : this.http.post<Category>(`${this.baseUrl}/categories`, data));
  }

  saveSupplier(data: { name: string; phone: string; email: string }, id?: number): Promise<Supplier> {
    return firstValueFrom(id
      ? this.http.put<Supplier>(`${this.baseUrl}/suppliers/${id}`, data)
      : this.http.post<Supplier>(`${this.baseUrl}/suppliers`, data));
  }

  saveItem(data: { category_id: number; name: string; price: number; quantity: number }, id?: number): Promise<Item> {
    return firstValueFrom(id
      ? this.http.put<Item>(`${this.baseUrl}/items/${id}`, data)
      : this.http.post<Item>(`${this.baseUrl}/items`, data));
  }

  remove(resource: 'categories' | 'suppliers' | 'items', id: number): Promise<void> {
    return firstValueFrom(this.http.delete<void>(`${this.baseUrl}/${resource}/${id}`));
  }

  savePurchase(data: { supplier_id: number; purchase_date: string; items: object[] }): Promise<Purchase> {
    return firstValueFrom(this.http.post<Purchase>(`${this.baseUrl}/purchases`, data));
  }

  saveSale(data: { sale_date: string; items: object[] }): Promise<Sale> {
    return firstValueFrom(this.http.post<Sale>(`${this.baseUrl}/sales`, data));
  }
}
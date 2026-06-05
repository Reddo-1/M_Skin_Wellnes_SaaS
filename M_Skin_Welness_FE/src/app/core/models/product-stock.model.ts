export interface ProductStockProduct {
  id: number;
  name: string;
  minimum_stock: string;
  doses_per_package: number;
}

export interface ProductStock {
  id: number;
  center_id: number;
  product_id: number;
  current_quantity: string;
  product?: ProductStockProduct;
  created_at: string | null;
  updated_at: string | null;
}

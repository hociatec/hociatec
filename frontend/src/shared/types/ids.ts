type Brand<TValue, TBrand extends string> = TValue & { readonly __brand: TBrand };

export type OrderId = Brand<number, 'OrderId'>;
export type ProductId = Brand<number, 'ProductId'>;
export type QuoteId = Brand<number, 'QuoteId'>;
export type UserId = Brand<number, 'UserId'>;

export const toOrderId = (value: number): OrderId => value as OrderId;
export const toProductId = (value: number): ProductId => value as ProductId;
export const toQuoteId = (value: number): QuoteId => value as QuoteId;
export const toUserId = (value: number): UserId => value as UserId;

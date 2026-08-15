import {
  requireArray,
  requireBoolean,
  requireNumber,
  requireRecord,
  requireString,
  optionalNumber,
  optionalString,
} from '@/shared/lib/contractValidation';
import { parseCatalogProduct } from '@/features/catalog/publicApi';
import type { Cart, CartPromotion } from './types/cart';

const DISCOUNT_TYPES = new Set(['percent', 'fixed_cents']);
const VOUCHER_STATUSES = new Set(['none', 'applied', 'invalid', 'ineligible']);

const parseDiscountType = (value: unknown): CartPromotion['discountType'] => {
  const type = requireString(value);
  if (!DISCOUNT_TYPES.has(type)) {
    throw new Error('Réponse panier invalide.');
  }

  return type as CartPromotion['discountType'];
};

const parsePromotion = (value: unknown): CartPromotion => {
  const promotion = requireRecord(value);

  return {
    id: requireNumber(promotion.id),
    name: requireString(promotion.name),
    slug: requireString(promotion.slug),
    description: optionalString(promotion.description) ?? null,
    discountType: parseDiscountType(promotion.discountType),
    discountValue: requireNumber(promotion.discountValue),
    discountAmountCents: requireNumber(promotion.discountAmountCents),
    audienceKey: requireString(promotion.audienceKey),
    criteria: requireRecord(promotion.criteria) as CartPromotion['criteria'],
    isActive: requireBoolean(promotion.isActive),
    startsAt: optionalString(promotion.startsAt) ?? null,
    endsAt: optionalString(promotion.endsAt) ?? null,
  };
};

const parseVoucher = (value: unknown): Cart['appliedVoucher'] => {
  if (value === null || value === undefined) return null;
  const voucher = requireRecord(value);

  return {
    id: requireNumber(voucher.id),
    name: requireString(voucher.name),
    code: requireString(voucher.code),
    description: optionalString(voucher.description) ?? null,
    discountType: parseDiscountType(voucher.discountType),
    discountValue: requireNumber(voucher.discountValue),
    discountAmountCents: requireNumber(voucher.discountAmountCents),
    isActive: requireBoolean(voucher.isActive),
    startsAt: optionalString(voucher.startsAt) ?? null,
    endsAt: optionalString(voucher.endsAt) ?? null,
  };
};

export const parseCart = (value: unknown): Cart => {
  const cart = requireRecord(value);
  const voucherCodeStatus = optionalString(cart.voucherCodeStatus) ?? undefined;
  const enteredVoucherCode = optionalString(cart.enteredVoucherCode);
  if (voucherCodeStatus !== undefined && !VOUCHER_STATUSES.has(voucherCodeStatus)) {
    throw new Error('Réponse panier invalide.');
  }

  const parsed: Cart = {
    token: requireString(cart.token),
    items: requireArray(cart.items).map((value) => {
      const item = requireRecord(value);

      const rentalMonths = optionalNumber(item.rentalMonths);
      const rentalStartDate = optionalString(item.rentalStartDate);
      const rentalEndDate = optionalString(item.rentalEndDate);

      return {
        id: requireNumber(item.id),
        product: parseCatalogProduct(item.product),
        quantity: requireNumber(item.quantity),
        linePriceCents: requireNumber(item.linePriceCents),
        ...(rentalMonths !== undefined ? { rentalMonths } : {}),
        ...(rentalStartDate !== undefined ? { rentalStartDate } : {}),
        ...(rentalEndDate !== undefined ? { rentalEndDate } : {}),
      };
    }),
    totalQuantity: requireNumber(cart.totalQuantity),
    subtotalPriceCents: requireNumber(cart.subtotalPriceCents),
    discountAmountCents: requireNumber(cart.discountAmountCents),
    totalPriceCents: requireNumber(cart.totalPriceCents),
    appliedPromotion:
      cart.appliedPromotion === null || cart.appliedPromotion === undefined
        ? null
        : parsePromotion(cart.appliedPromotion),
    eligiblePromotions: requireArray(cart.eligiblePromotions).map(parsePromotion),
    appliedVoucher: parseVoucher(cart.appliedVoucher),
    updatedAt: requireString(cart.updatedAt),
  };

  if (enteredVoucherCode !== undefined) {
    parsed.enteredVoucherCode = enteredVoucherCode;
  }
  if (voucherCodeStatus) {
    parsed.voucherCodeStatus = voucherCodeStatus as NonNullable<Cart['voucherCodeStatus']>;
  }

  return parsed;
};

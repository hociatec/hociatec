import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useCartPageController } from '@/features/cart/hooks/useCartPageController';
import {
  CartItemsList,
  CartPageHeader,
  EmptyCartState,
} from '@/features/cart/components/CartPageBaseSections';
import { CartSummarySidebar } from '@/features/cart/components/CartSummarySidebar';
import './CartPage.css';

export const CartPage = () => {
  useDocumentTitle('Mon panier');

  const {
    addresses,
    addressesError,
    addressesLoading,
    authStatus,
    cart,
    error,
    handleApplyPromotionCode,
    handleCheckout,
    handleClear,
    handleClearPromotionCode,
    handleDecrease,
    handleIncrease,
    handleRemoveItem,
    hasItems,
    isApplyingPromotionCode,
    isCheckout,
    isLoading,
    isProductPending,
    isPromotionCodeEmpty,
    isClearing,
    navigate,
    promotionCode,
    selectedAddressId,
    setSelectedAddressId,
    setPromotionCode,
    updateRentalMonths,
  } = useCartPageController();

  return (
    <SiteLayout headerVariant="light">
      <div className="cart-page">
        <CartPageHeader
          hasItems={hasItems}
          isClearing={isClearing}
          onClear={handleClear}
          totalQuantity={cart?.totalQuantity ?? 0}
        />

        {isLoading && <LoadingState>Chargement de votre panier...</LoadingState>}
        {error && <FeedbackMessage>{error}</FeedbackMessage>}

        {hasItems ? (
          <div className="cart-page__content">
            <CartItemsList
              items={cart?.items ?? []}
              isProductPending={isProductPending}
              onDecrease={handleDecrease}
              onIncrease={handleIncrease}
              onRemove={handleRemoveItem}
              onUpdateRentalMonths={updateRentalMonths}
            />
            <CartSummarySidebar
              addresses={addresses}
              addressesError={addressesError}
              addressesLoading={addressesLoading}
              authStatus={authStatus}
              cart={cart}
              isApplyingPromotionCode={isApplyingPromotionCode}
              isCheckout={isCheckout}
              isPromotionCodeEmpty={isPromotionCodeEmpty}
              promotionCode={promotionCode}
              selectedAddressId={selectedAddressId}
              onAddAddress={() => navigate('/profile/addresses')}
              onAddressSelect={setSelectedAddressId}
              onApplyPromotionCode={handleApplyPromotionCode}
              onCheckout={handleCheckout}
              onClearPromotionCode={handleClearPromotionCode}
              onContinueShopping={() => navigate('/catalogue/recherche')}
              onPromotionCodeChange={setPromotionCode}
            />
          </div>
        ) : (
          !isLoading && <EmptyCartState onExplore={() => navigate('/catalogue/recherche')} />
        )}
      </div>
    </SiteLayout>
  );
};

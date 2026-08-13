import Foundation

extension ProductDetailViewModel {
    func loadInitialData(cart: CartViewModel) async {
        await loadProductDetail(cart: cart)
        await loadReviews(page: 1)
        await refreshFavorite()
    }

    func loadProductDetail(cart: CartViewModel) async {
        guard !isLoadingDetail else { return }
        isLoadingDetail = true
        detailError = nil
        defer { isLoadingDetail = false }

        do {
            product = try await loadDetailUseCase.execute(slug: product.slug)
            if product.sellingType == .rental,
               let existing = cart.cart?.items.first(where: { $0.product.id == product.id })?.rentalMonths {
                rentalMonths = max(1, existing)
            }
        } catch {
            detailError = error.localizedDescription
        }
    }

    func loadReviews(page: Int = 1) async {
        guard !isLoadingReviews else { return }
        isLoadingReviews = true
        reviewsError = nil
        defer { isLoadingReviews = false }

        do {
            let data = try await loadReviewsUseCase.execute(slug: product.slug, page: page, perPage: reviewsPerPage)
            reviewsPerPage = data.meta.perPage
            reviewsTotal = data.meta.total
            reviewsAverage = data.meta.average
            if page == 1 {
                reviews = data.items
            } else {
                reviews.append(contentsOf: data.items)
            }
        } catch {
            reviewsError = error.localizedDescription
        }
    }
}

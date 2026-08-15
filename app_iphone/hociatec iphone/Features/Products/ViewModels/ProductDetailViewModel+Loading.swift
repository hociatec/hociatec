import Foundation

extension ProductDetailViewModel {
    func loadInitialData(cart: CartViewModel) async {
        guard !hasLoadedInitialDataOnce else { return }

        async let detailTask: Void = loadProductDetail(cart: cart)
        async let reviewsTask: Void = loadReviews(page: 1)
        async let favoriteTask: Void = refreshFavorite()
        _ = await (detailTask, reviewsTask, favoriteTask)
        hasLoadedInitialDataOnce = true
    }

    func loadProductDetail(cart: CartViewModel) async {
        guard !isLoadingDetail else { return }
        isLoadingDetail = true
        detailError = nil
        defer { isLoadingDetail = false }

        do {
            product = try await loadDetailUseCase.execute(slug: product.slug, sellingType: product.sellingType)
            syncRentalSelection(from: cart)
        } catch {
            detailError = error.localizedDescription
        }
    }

    func loadReviews(page: Int = 1) async {
        if page == 1 && hasLoadedFirstReviewsPageOnce { return }
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
                hasLoadedFirstReviewsPageOnce = true
            } else {
                reviews.append(contentsOf: data.items)
            }
        } catch {
            reviewsError = error.localizedDescription
        }
    }
}

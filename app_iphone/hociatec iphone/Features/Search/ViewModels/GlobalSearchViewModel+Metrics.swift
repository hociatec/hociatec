import Foundation

extension GlobalSearchViewModel {
    var totalResults: Int {
        visibleTotal(for: .products, count: productTotal)
            + visibleTotal(for: .services, count: serviceTotal)
            + visibleTotal(for: .trainings, count: trainingTotal)
            + visibleTotal(for: .news, count: newsTotal)
    }

    func hasVisibleResults(for filter: GlobalSearchFilter) -> Bool {
        sectionTotal(for: filter) > 0
    }

    func sectionTotal(for filter: GlobalSearchFilter) -> Int {
        switch filter {
        case .all:
            return totalResults
        case .products:
            return productTotal
        case .services:
            return serviceTotal
        case .trainings:
            return trainingTotal
        case .news:
            return newsTotal
        }
    }

    func shouldLoad(_ filter: GlobalSearchFilter) -> Bool {
        selectedFilter == .all || selectedFilter == filter
    }

    private func visibleTotal(for filter: GlobalSearchFilter, count: Int) -> Int {
        shouldLoad(filter) ? count : 0
    }
}

import Foundation

extension GlobalSearchViewModel {
    func shouldShow(_ filter: GlobalSearchFilter) -> Bool {
        selectedFilter == .all || selectedFilter == filter
    }
}

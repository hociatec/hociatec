import Foundation

enum GlobalSearchSortOption: String, CaseIterable, Identifiable {
    case relevance
    case alphabeticalAsc
    case alphabeticalDesc

    var id: String { rawValue }

    var label: String {
        switch self {
        case .relevance:
            return "Pertinence"
        case .alphabeticalAsc:
            return "A à Z"
        case .alphabeticalDesc:
            return "Z à A"
        }
    }
}

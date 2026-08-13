import Foundation

enum SupportReasons: String, CaseIterable {
    case other
    case delivery
    case billing
    case technical
    case returnRequest = "return"

    var label: String {
        switch self {
        case .other: return "Autre"
        case .delivery: return "Livraison"
        case .billing: return "Facturation"
        case .technical: return "Technique"
        case .returnRequest: return "Retour"
        }
    }
}

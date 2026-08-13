import Foundation

enum BetaProgramLabels {
    static let severities = ["low", "normal", "high", "critical"]

    static func statusLabel(for value: String?) -> String {
        switch value {
        case "pending": return "En attente"
        case "accepted": return "Accepté"
        case "paused": return "En pause"
        case "rejected": return "Refusé"
        default: return "Non renseigné"
        }
    }

    static func campaignLabel(for value: String) -> String {
        switch value {
        case "draft": return "Brouillon"
        case "active": return "Active"
        case "closed": return "Clôturée"
        default: return value
        }
    }

    static func reportStatusLabel(for value: String) -> String {
        switch value {
        case "submitted": return "Soumis"
        case "under_review": return "En cours d’analyse"
        case "need_info": return "Informations nécessaires"
        case "planned": return "Correction planifiée"
        case "resolved": return "Corrigé"
        case "duplicate": return "Doublon"
        case "rejected": return "Rejeté"
        default: return value
        }
    }

    static func severityLabel(for value: String) -> String {
        switch value {
        case "low": return "Faible"
        case "normal": return "Normale"
        case "high": return "Haute"
        case "critical": return "Critique"
        default: return value
        }
    }
}

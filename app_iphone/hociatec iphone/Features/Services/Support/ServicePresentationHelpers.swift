import Foundation

func serviceBillingModeLabel(_ value: String?) -> String {
    let normalized = (value ?? "")
        .folding(options: .diacriticInsensitive, locale: .current)
        .trimmingCharacters(in: .whitespacesAndNewlines)
        .lowercased()

    switch normalized {
    case "", "prix fixe":
        return "Prix fixe"
    case "heure", "horaire":
        return "Horaire"
    case "jour":
        return "À la journée"
    case "intervention":
        return "Par intervention"
    case "audit":
        return "Audit"
    case "installation":
        return "Installation"
    case "maintenance":
        return "Maintenance"
    default:
        return value?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? (value ?? "Prix fixe") : "Prix fixe"
    }
}

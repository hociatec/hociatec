import Foundation

extension AccountViewModel {
    func normalizedBirthDate(_ value: String) -> String {
        if let date = AccountViewModel.birthDateFormatter.date(from: value) {
            return AccountViewModel.birthDateFormatter.string(from: date)
        }
        return AccountViewModel.birthDateFormatter.string(from: Date())
    }

    func normalizedGender(_ value: String) -> String {
        let cleaned = value.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        switch cleaned {
        case "homme":
            return "homme"
        case "femme":
            return "femme"
        case "autre":
            return "autre"
        default:
            return "autre"
        }
    }
}

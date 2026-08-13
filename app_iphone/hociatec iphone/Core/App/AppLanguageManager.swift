import Foundation

/// Manages the app's preferred language by updating the AppleLanguages user default.
///
/// Notes:
/// - This influences localization bundle lookup for this app only.
/// - Call very early (e.g., in your `@main` App init) before any UI is created.
/// - Does not change system language or VoiceOver language settings.
final class AppLanguageManager {
    /// Force French as the preferred language for the app if not already set.
    static func setFrenchAsPreferredLanguage() {
        setPreferredLanguage(code: "fr")
    }

    /// Set the preferred language using a BCP-47 code (e.g., "fr", "fr-FR").
    /// The list will fall back to the provided regional variant, then English.
    static func setPreferredLanguage(code: String) {
        let normalized = code.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !normalized.isEmpty else { return }
        var variants: [String] = [normalized]
        // If the code is language-only (e.g., "fr"), add a common regional variant fallback.
        if !normalized.contains("-") {
            variants.append("\(normalized)-\(normalized.uppercased())")
        }
        // Always add English as last resort to avoid an empty bundle.
        variants.append("en")

        let defaults = UserDefaults.standard
        let current = (defaults.array(forKey: "AppleLanguages") as? [String]) ?? []
        if let first = current.first, first.lowercased().hasPrefix(normalized.lowercased()) {
            // Already prioritized; nothing to do.
            return
        }
        defaults.set(variants, forKey: "AppleLanguages")
        defaults.synchronize()
    }

    /// Returns the current AppleLanguages array for debugging.
    static func currentAppleLanguages() -> [String] {
        (UserDefaults.standard.array(forKey: "AppleLanguages") as? [String]) ?? []
    }
}

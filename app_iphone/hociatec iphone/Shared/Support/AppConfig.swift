import Foundation

enum AppConfig {
    static let apiBaseURL = URL(string: "https://api.hociatec.fr")!
    static let websiteBaseURL = URL(string: "https://hociatec.fr")!
    static let websiteHost = "hociatec.fr"
    static let contactEmail = "contact@hociatec.fr"
    static let companyPostalAddress = "2 allée Anatoli Vaisser, 92600 Asnières-sur-Seine, France"

    enum Social {
        static let facebook = URL(string: "https://www.facebook.com/hociatec")!
        static let linkedIn = URL(string: "https://www.linkedin.com/company/hociatec")!
        static let tikTok = URL(string: "https://www.tiktok.com/@hociatec")!
        static let x = URL(string: "https://x.com/hociatec")!
        static let instagram = URL(string: "https://www.instagram.com/hociatec")!
    }

    static func websiteURL(path: String) -> URL {
        let trimmedPath = path.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        return websiteBaseURL.appendingPathComponent(trimmedPath)
    }
}

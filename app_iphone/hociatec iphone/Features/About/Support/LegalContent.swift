import Foundation

struct LegalSection: Identifiable {
    let id = UUID()
    let title: String
    let paragraphs: [String]
}

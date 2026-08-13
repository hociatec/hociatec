import Foundation

enum NewsDetailPresentation {
    static func commentDate(_ date: Date) -> String {
        date.formatted(date: .abbreviated, time: .shortened)
    }

    static func canSubmitComment(_ comment: String) -> Bool {
        comment.trimmingCharacters(in: .whitespacesAndNewlines).count >= 3
    }
}

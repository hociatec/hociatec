import Foundation

extension BetaProgramViewModel {
    func statusLabel(for value: String?) -> String {
        BetaProgramLabels.statusLabel(for: value)
    }

    func campaignLabel(for value: String) -> String {
        BetaProgramLabels.campaignLabel(for: value)
    }

    func reportStatusLabel(for value: String) -> String {
        BetaProgramLabels.reportStatusLabel(for: value)
    }

    func severityLabel(for value: String) -> String {
        BetaProgramLabels.severityLabel(for: value)
    }
}

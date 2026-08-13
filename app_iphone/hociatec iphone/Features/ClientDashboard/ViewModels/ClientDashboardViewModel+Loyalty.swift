import Foundation

extension ClientDashboardViewModel {
    func convertLoyalty() async {
        let points = normalizedConvertPoints
        guard points > 0, points <= loyalty.points else { return }
        isLoading = true
        error = nil
        conversionMessage = nil
        defer { isLoading = false }

        do {
            let result = try await workspaceService.convertLoyalty(points: points)
            loyalty = result.loyalty
            convertPoints = ""
            conversionMessage = "Bon \(result.voucher.code) créé."
            syncConvertPointsIfNeeded()
        } catch {
            self.error = error.localizedDescription
        }
    }

    var normalizedConvertPoints: Int {
        let trimmed = convertPoints.trimmingCharacters(in: .whitespacesAndNewlines)
        return Int(trimmed) ?? 0
    }

    var canConvert: Bool {
        let points = normalizedConvertPoints
        return points > 0 && points <= loyalty.points && !isLoading
    }

    var convertedEuroCents: Int {
        guard loyalty.pointsPerEuroConverted > 0 else { return 0 }
        return (normalizedConvertPoints / loyalty.pointsPerEuroConverted) * 100
    }

    func syncConvertPointsIfNeeded() {
        let current = normalizedConvertPoints
        if current > 0, current <= loyalty.points {
            return
        }

        let suggested = min(max(loyalty.pointsPerEuroConverted, 0), loyalty.points)
        convertPoints = suggested > 0 ? String(suggested) : ""
    }
}

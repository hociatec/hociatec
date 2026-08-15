import Foundation

@MainActor
final class MyRentalsViewModel: ObservableObject {
    @Published var upcoming: [RentalItem] = []
    @Published var past: [RentalItem] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var submittingActionKey: String?

    private let service: RentalServing
    private var hasLoadedOnce = false
    private var loadRequestID = 0

    init(service: RentalServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let data = try await service.myRentals()
            guard requestID == loadRequestID else { return }
            upcoming = data.upcoming
            past = data.past
            hasLoadedOnce = true
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func requestChange(for rental: RentalItem, action: RentalRequestAction, requestedEndDate: String) async {
        let actionKey = "\(action.rawValue):\(rental.orderItemId)"
        submittingActionKey = actionKey
        error = nil
        successMessage = nil

        do {
            let updated = try await service.requestRentalChange(
                orderItemId: rental.orderItemId,
                action: action,
                requestedEndDate: requestedEndDate
            )
            apply(updated)
            if action == .extend, updated.request.status != "pending" {
                successMessage = "La location est prolongée jusqu’au \(DatePresentation.formatAPIDay(updated.endDate))."
            } else {
                successMessage = action == .extend
                    ? "Votre demande de prolongation a bien été enregistrée."
                    : "Votre demande de fin anticipée a bien été enregistrée."
            }
        } catch {
            self.error = error.localizedDescription
        }

        if submittingActionKey == actionKey {
            submittingActionKey = nil
        }
    }

    private func apply(_ updated: RentalItem) {
        if let index = upcoming.firstIndex(where: { $0.id == updated.id }) {
            upcoming[index] = updated
            return
        }
        if let index = past.firstIndex(where: { $0.id == updated.id }) {
            past[index] = updated
        }
    }
}

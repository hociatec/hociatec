import Combine
import Foundation

@MainActor
final class MyRentalsViewModel: ObservableObject {
    enum ReturnMode: String {
        case pickupHome = "pickup_home"
        case dropoffStore = "dropoff_store"
    }

    @Published var upcoming: [RentalItem] = []
    @Published var past: [RentalItem] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var submittingActionKey: String?
    @Published var checkoutURL: URL?

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
        checkoutURL = nil

        do {
            let result = try await service.requestRentalChange(
                orderItemId: rental.orderItemId,
                action: action,
                requestedEndDate: requestedEndDate
            )
            let updated = result.rental
            apply(updated)
            if let rawURL = result.checkout?.checkoutUrl, let url = URL(string: rawURL) {
                checkoutURL = url
                successMessage = "Redirection vers le paiement de la prolongation."
            } else if action == .extend, updated.request.status != "pending" {
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

    func planReturn(for rental: RentalItem, mode: ReturnMode, requestedDate: String) async {
        let actionKey = "return:\(rental.orderItemId)"
        submittingActionKey = actionKey
        error = nil
        successMessage = nil

        do {
            let updated = try await service.planRentalReturn(
                orderItemId: rental.orderItemId,
                mode: mode.rawValue,
                requestedDate: requestedDate
            )
            apply(updated)
            let dateLabel = DatePresentation.formatAPIDay(updated.returnPlan.requestedDate)
            let modeLabel = mode == .pickupHome ? "Récupération à domicile" : "Dépôt en boutique"
            successMessage = "\(modeLabel) planifié pour le \(dateLabel)."
        } catch {
            self.error = error.localizedDescription
        }

        if submittingActionKey == actionKey {
            submittingActionKey = nil
        }
    }

    func terminateRental(
        for rental: RentalItem,
        requestedEndDate: String,
        returnMode: ReturnMode,
        returnRequestedDate: String
    ) async {
        let actionKey = "terminate:\(rental.orderItemId)"
        submittingActionKey = actionKey
        error = nil
        successMessage = nil

        do {
            let updated = try await service.terminateRental(
                orderItemId: rental.orderItemId,
                requestedEndDate: requestedEndDate,
                returnMode: returnMode.rawValue,
                returnRequestedDate: returnRequestedDate
            )
            apply(updated)
            successMessage = updated.request.type == RentalRequestAction.endEarly.rawValue
                ? "Votre fin de location et la restitution ont bien ete enregistrees."
                : "\(returnMode == .pickupHome ? "Recuperation a domicile" : "Depot en boutique") planifie pour le \(DatePresentation.formatAPIDay(updated.returnPlan.requestedDate))."
        } catch {
            self.error = error.localizedDescription
        }

        if submittingActionKey == actionKey {
            submittingActionKey = nil
        }
    }

    func resumePendingExtensionPayment(for rental: RentalItem) {
        guard let rawURL = rental.extensionState.checkoutUrl, let url = URL(string: rawURL) else {
            error = "Impossible de retrouver le lien de paiement de cette prolongation."
            return
        }

        checkoutURL = url
    }

    func cancelPendingExtensionPayment(for rental: RentalItem) async {
        guard let stripeSessionId = rental.extensionState.checkoutSessionId, !stripeSessionId.isEmpty else {
            error = "Impossible de retrouver la session de paiement a annuler."
            return
        }

        let actionKey = "cancel-payment:\(rental.orderItemId)"
        submittingActionKey = actionKey
        error = nil
        successMessage = nil

        do {
            _ = try await service.cancelPendingExtensionCheckout(stripeSessionId: stripeSessionId)
            await load(force: true)
            successMessage = "La tentative de paiement a ete annulee. Vous pouvez relancer une prolongation."
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

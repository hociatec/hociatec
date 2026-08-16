import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct AppointmentDetailScreen: View {
    let appointment: AppointmentSummary
    @ObservedObject var viewModel: MyAppointmentsViewModel
    let service: AppointmentServing
    @State private var isCancelling = false
    @State private var confirmationDialog: FeedbackDialogState?
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        Form {
            AppointmentDetailsSection(appointment: appointment)
            AppointmentCancelSection(
                canCancel: appointment.canCancel,
                isCancelling: isCancelling,
            ) {
                confirmationDialog = FeedbackDialogState(
                    title: "Annuler ce rendez-vous ?",
                    message: "Cette action est irréversible. Le rendez-vous sera marqué comme annulé.",
                    primaryButton: .cancel("Retour"),
                    secondaryButton: .destructive("Confirmer l'annulation") {
                        guard !isCancelling else { return }
                        isCancelling = true
                        Task {
                            let success = await viewModel.cancel(appointmentID: appointment.id)
#if canImport(UIKit)
                            UINotificationFeedbackGenerator().notificationOccurred(success ? .success : .error)
#endif
                            isCancelling = false
                            if success {
                                dismiss()
                            }
                        }
                    }
                )
            }
            if appointment.canReschedule {
                Section {
                    NavigationLink {
                        AppointmentBookingView(
                            service: service,
                            mode: .rescheduling(appointment)
                        ) { _ in
                            Task {
                                await viewModel.load(force: true)
                            }
                            dismiss()
                        }
                    } label: {
                        Text("Choisir un nouveau créneau")
                    }
                }
            }
        }
        .navigationTitle("Rendez-vous")
        .navigationBarTitleDisplayMode(.inline)
        .feedbackDialog($confirmationDialog)
    }
}

struct AppointmentConfirmationView: View {
    @ObservedObject var viewModel: AppointmentBookingViewModel
    let slot: AppointmentSlot
    let onCompleted: ((AppointmentSummary) -> Void)?
    @EnvironmentObject private var account: AccountViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @Environment(\.dismiss) private var dismiss
    @State private var isConfirming = false

    var body: some View {
        Form {
            AppointmentConfirmationSummarySection(
                prestation: selectedPrestation,
                slot: slot
            )
            AppointmentLoginNoticeSection(isLoggedIn: account.isLoggedIn)
            AppointmentConfirmActionSection(
                isLoggedIn: account.isLoggedIn,
                isConfirming: isConfirming,
                onRequireLogin: {
                    navigation.showTab(4)
                    dismiss()
                }
            ) {
                await confirmAppointment()
            }
        }
        .navigationTitle("Récapitulatif")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var selectedPrestation: AppointmentPrestation? {
        AppointmentBookingPresentation.selectedPrestation(
            prestations: viewModel.prestations,
            selectedPrestationId: viewModel.selectedPrestationId
        )
    }

    private func confirmAppointment() async {
        guard !isConfirming else { return }
        isConfirming = true
        let result = await viewModel.book(slot: slot)
        isConfirming = false
        if let result {
#if canImport(UIKit)
            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
            onCompleted?(result)
        }
    }
}

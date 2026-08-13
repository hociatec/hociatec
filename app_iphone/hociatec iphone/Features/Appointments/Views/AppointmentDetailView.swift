import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct AppointmentDetailScreen: View {
    let appointment: AppointmentSummary
    @ObservedObject var viewModel: MyAppointmentsViewModel
    @State private var isCancelling = false
    @State private var showCancelAlert = false
    @Environment(\.dismiss) private var dismiss

    private var timeRange: String {
        "\(timeFormatter.string(from: appointment.startAt)) - \(timeFormatter.string(from: appointment.endAt))"
    }

    var body: some View {
        Form {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            Section("Détails") {
                LabeledContent("Prestation") { Text(appointment.prestation.name) }
                LabeledContent("Date") { Text(dayFormatter.string(from: appointment.startAt)) }
                LabeledContent("Heure") { Text(timeRange) }
                if let status = appointment.status {
                    LabeledContent("Statut") { Text(status.capitalized) }
                }
            }

            if appointment.canCancel {
                Section("Actions") {
                    Button(role: .destructive) {
                        showCancelAlert = true
                    } label: {
                        if isCancelling {
                            ProgressView().frame(maxWidth: .infinity)
                        } else {
                            Text("Annuler le rendez-vous").frame(maxWidth: .infinity)
                        }
                    }
                    .disabled(isCancelling)
                    .accessibilityLabel("Annuler ce rendez-vous")
                    .accessibilityHint("Annule ce rendez-vous et revient à la liste")
                }
            }
        }
        .navigationTitle("Rendez-vous")
        .navigationBarTitleDisplayMode(.inline)
        .alert("Annuler ce rendez-vous ?", isPresented: $showCancelAlert) {
            Button("Retour", role: .cancel) {
                showCancelAlert = false
            }
            Button("Confirmer l’annulation", role: .destructive) {
                guard !isCancelling else { return }
                isCancelling = true
                showCancelAlert = false
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
        } message: {
            Text("Cette action est irréversible. Le rendez-vous sera marqué comme annulé.")
        }
    }
}

struct AppointmentConfirmationView: View {
    @ObservedObject var viewModel: AppointmentBookingViewModel
    let slot: AppointmentSlot
    @EnvironmentObject private var account: AccountViewModel
    @Environment(\.dismiss) private var dismiss
    @State private var isConfirming = false

    private var selectedPrestation: AppointmentPrestation? {
        viewModel.prestations.first(where: { $0.id == viewModel.selectedPrestationId })
    }

    var body: some View {
        Form {
            Section {
                if let prestation = selectedPrestation {
                    LabeledContent("Prestation") { Text(prestation.name) }
                    LabeledContent("Durée") { Text("\(prestation.durationMinutes) min") }
                    LabeledContent("Prix") { Text(PriceFormatter.format(cents: prestation.priceCents)) }
                }
                LabeledContent("Date") { Text(dayFormatter.string(from: slot.startAt)) }
                LabeledContent("Heure") { Text("\(timeFormatter.string(from: slot.startAt)) - \(timeFormatter.string(from: slot.endAt))") }
            }

            if let message = viewModel.bookingMessage {
                Section {
                    Label(message, systemImage: "checkmark.seal.fill")
                        .foregroundStyle(.green)
                }
            }

            if !account.isLoggedIn {
                Section {
                    Text("Connectez-vous pour valider.")
                        .foregroundStyle(.secondary)
                }
            }

            Section {
                Button {
                    Task {
                        guard !isConfirming else { return }
                        isConfirming = true
                        let result = await viewModel.book(slot: slot)
                        isConfirming = false
                        if result != nil {
#if canImport(UIKit)
                            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
                            try? await Task.sleep(nanoseconds: 1_000_000_000)
                            dismiss()
                        }
                    }
                } label: {
                    if isConfirming {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                    } else {
                        Text("Valider")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(!account.isLoggedIn || isConfirming)
            }
        }
        .navigationTitle("Récapitulatif")
        .navigationBarTitleDisplayMode(.inline)
    }
}

import SwiftUI

struct AppointmentErrorSection: View {
    let error: String?

    var body: some View {
        if let error, !error.isEmpty {
            Section {
                Text(error)
                    .foregroundStyle(.red)
            }
        }
    }
}

struct AppointmentDetailsSection: View {
    let appointment: AppointmentSummary

    var body: some View {
        Section("Détails") {
            LabeledContent("Prestation") { Text(appointment.prestation.name) }
            LabeledContent("Date") { Text(dayFormatter.string(from: appointment.startAt)) }
            LabeledContent("Heure") { Text(timeRange) }
            if let status = appointment.status {
                LabeledContent("Statut") { Text(status.capitalized) }
            }
        }
    }

    private var timeRange: String {
        "\(timeFormatter.string(from: appointment.startAt)) - \(timeFormatter.string(from: appointment.endAt))"
    }
}

struct AppointmentCancelSection: View {
    let canCancel: Bool
    let isCancelling: Bool
    let onCancel: () -> Void

    var body: some View {
        if canCancel {
            Section("Actions") {
                Button(role: .destructive, action: onCancel) {
                    if isCancelling {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                    } else {
                        Text("Annuler le rendez-vous")
                            .frame(maxWidth: .infinity)
                    }
                }
                .disabled(isCancelling)
                .accessibilityLabel("Annuler ce rendez-vous")
                .accessibilityHint("Annule ce rendez-vous et revient à la liste")
            }
        }
    }
}

struct AppointmentConfirmationSummarySection: View {
    let prestation: AppointmentPrestation?
    let slot: AppointmentSlot

    var body: some View {
        Section {
            if let prestation {
                LabeledContent("Prestation") { Text(prestation.name) }
                LabeledContent("Durée") { Text("\(prestation.durationMinutes) min") }
                LabeledContent("Prix") { Text(PriceFormatter.format(cents: prestation.priceCents)) }
            }
            LabeledContent("Date") { Text(dayFormatter.string(from: slot.startAt)) }
            LabeledContent("Heure") { Text(AppointmentBookingPresentation.timeRange(for: slot)) }
        }
    }
}

struct AppointmentBookingMessageSection: View {
    let message: String?

    var body: some View {
        if let message, !message.isEmpty {
            Section {
                Label(message, systemImage: "checkmark.seal.fill")
                    .foregroundStyle(.green)
            }
        }
    }
}

struct AppointmentLoginNoticeSection: View {
    let isLoggedIn: Bool

    var body: some View {
        if !isLoggedIn {
            Section {
                Text("Vous pouvez choisir votre créneau librement. Ouvrez l’onglet Compte pour vous connecter avant la confirmation finale.")
                    .foregroundStyle(.secondary)
            }
        }
    }
}

struct AppointmentConfirmActionSection: View {
    let isLoggedIn: Bool
    let isConfirming: Bool
    let onRequireLogin: () -> Void
    let onConfirm: () async -> Void

    var body: some View {
        Section {
            Button {
                if isLoggedIn {
                    Task {
                        await onConfirm()
                    }
                } else {
                    onRequireLogin()
                }
            } label: {
                if isConfirming {
                    ProgressView()
                        .frame(maxWidth: .infinity)
                } else {
                    Text(isLoggedIn ? "Valider" : "Se connecter pour confirmer")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(isConfirming)
        }
    }
}

import SwiftUI

private struct TrainingSlotSelection {
    var dateKey: String
    var time: String
}

struct TrainingSessionsSection: View {
    let training: Training
    @ObservedObject var viewModel: TrainingDetailViewModel

    @EnvironmentObject private var account: AccountViewModel
    @EnvironmentObject private var navigation: AppNavigationState
    @Environment(\.openURL) private var openURL
    @State private var slotSelections: [Int: TrainingSlotSelection] = [:]

    var body: some View {
        Section("Sessions disponibles") {
            if viewModel.sessions.isEmpty {
                Text("Aucune session planifiée pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.sessions) { session in
                    TrainingSessionRow(
                        training: training,
                        session: session,
                        selection: selectionBinding(for: session),
                        isSubmitting: viewModel.submittingSessionId == session.id,
                        isLoggedIn: account.isLoggedIn,
                        onReserve: { Task { await reserve(session: session) } }
                    )
                }
            }
        }
    }

    private func selectionBinding(for session: TrainingSession) -> Binding<TrainingSlotSelection> {
        Binding(
            get: {
                if let existing = slotSelections[session.id] {
                    return existing
                }

                let availableDates = trainingAvailableDates(for: session)
                let availableTimes = trainingAvailableStartTimes(for: session, durationMinutes: training.durationMinutes)

                return TrainingSlotSelection(
                    dateKey: availableDates.first.map(trainingISODateFormatter.string(from:)) ?? "",
                    time: availableTimes.first ?? session.dailyStartTime
                )
            },
            set: { newValue in
                slotSelections[session.id] = newValue
            }
        )
    }

    private func reserve(session: TrainingSession) async {
        guard account.isLoggedIn else {
            navigation.showTab(4)
            return
        }

        let selection = selectionBinding(for: session).wrappedValue
        guard let startsAt = trainingStartDateTime(dateKey: selection.dateKey, time: selection.time) else {
            viewModel.error = "Choisissez une date et une heure de début."
            return
        }

        if let result = await viewModel.enroll(sessionId: session.id, startsAt: startsAt),
           let checkoutURL = result.checkoutURL {
            openURL(checkoutURL)
        }
    }
}

private struct TrainingSessionRow: View {
    let training: Training
    let session: TrainingSession
    @Binding var selection: TrainingSlotSelection
    let isSubmitting: Bool
    let isLoggedIn: Bool
    let onReserve: () -> Void

    private var availableDates: [Date] {
        trainingAvailableDates(for: session)
    }

    private var availableTimes: [String] {
        trainingAvailableStartTimes(for: session, durationMinutes: training.durationMinutes)
    }

    private var latestStartTime: String {
        trainingLatestStartTime(session: session, durationMinutes: training.durationMinutes)
    }

    private var computedEndTime: Date? {
        trainingEndDateTime(
            dateKey: selection.dateKey,
            time: selection.time,
            durationMinutes: training.durationMinutes
        )
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(
                "Du \(trainingDateFormatter.string(from: session.startsAt)) au \(trainingDateFormatter.string(from: session.endsAt))"
            )
            .fontWeight(.semibold)

            Text(
                "\(session.formatLabel) · \(session.capacity) participant(s) maximum par créneau · durée \(trainingDurationLabel(training.durationMinutes))"
            )
            .font(.footnote)
            .foregroundStyle(.secondary)

            Text("Début possible de \(session.dailyStartTime) à \(latestStartTime), fin au plus tard à \(session.dailyEndTime)")
                .font(.footnote)
                .foregroundStyle(.secondary)

            Text(
                session.includeWeekends
                    ? "Réservation possible week-end inclus"
                    : "Réservation du lundi au vendredi uniquement"
            )
            .font(.footnote)
            .foregroundStyle(.secondary)

            Text(session.format == "remote" ? "Lien transmis après confirmation" : (nonEmptyText(session.location) ?? "Lieu à confirmer"))
                .font(.footnote)
                .foregroundStyle(.secondary)

            if let computedEndTime {
                Text("Fin calculée : \(trainingDateTimeFormatter.string(from: computedEndTime))")
                    .font(.footnote.weight(.medium))
                    .padding(.vertical, 6)
                    .padding(.horizontal, 10)
                    .background(Color(.secondarySystemBackground))
                    .clipShape(RoundedRectangle(cornerRadius: 10))
            }

            Picker("Date souhaitée", selection: $selection.dateKey) {
                ForEach(availableDates, id: \.self) { date in
                    let key = trainingISODateFormatter.string(from: date)
                    Text(trainingDateFormatter.string(from: date)).tag(key)
                }
            }

            Picker("Heure de début souhaitée", selection: $selection.time) {
                ForEach(availableTimes, id: \.self) { time in
                    Text(time).tag(time)
                }
            }

            Button(action: onReserve) {
                if isSubmitting {
                    ProgressView()
                        .frame(maxWidth: .infinity)
                } else {
                    Text(isLoggedIn ? "Réserver" : "Se connecter pour réserver")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(isSubmitting || availableDates.isEmpty || availableTimes.isEmpty)
        }
        .padding(.vertical, 6)
    }
}

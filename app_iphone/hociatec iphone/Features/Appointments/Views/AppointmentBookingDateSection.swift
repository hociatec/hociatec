import SwiftUI

struct AppointmentBookingProgressSection: View {
    let step: AppointmentBookingViewModel.Step

    var body: some View {
        Section("Réservation en 3 étapes") {
            VStack(alignment: .leading, spacing: 8) {
                Text("Choisissez d'abord la prestation, puis un jour disponible, puis l'horaire qui vous convient.")
                    .foregroundStyle(.secondary)

                Text(currentStepTitle)
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(.primary)

                Text(currentStepDescription)
                    .font(.footnote.weight(.semibold))
                    .foregroundStyle(.secondary)
            }
            .padding(.vertical, 4)
        }
    }

    private var currentStepTitle: String {
        switch step {
        case .prestation:
            return "Étape 1/3"
        case .day:
            return "Étape 2/3"
        case .slot:
            return "Étape 3/3"
        }
    }

    private var currentStepDescription: String {
        switch step {
        case .prestation:
            return "Choix de la prestation."
        case .day:
            return "Choix du jour de rendez-vous."
        case .slot:
            return "Choix du créneau horaire."
        }
    }
}

struct AppointmentBookingCalendarSection: View {
    let visibleMonth: Date
    let availableDays: [Date]
    let selectedDate: Date?
    let isLoading: Bool
    let error: String?
    let onSelectDay: (Date) -> Void
    let onPreviousMonth: () -> Void
    let onNextMonth: () -> Void
    let onCurrentMonth: () -> Void
    let onBack: () -> Void

    private let columns = Array(repeating: GridItem(.flexible(), spacing: 8), count: 7)

    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 16) {
                if let error, !error.isEmpty {
                    Text(error)
                        .foregroundStyle(.red)
                }

                calendarToolbar

                Text(AppointmentBookingPresentation.monthTitle(for: visibleMonth).capitalized)
                    .font(.headline)

                if isLoading {
                    ProgressView("Chargement des créneaux disponibles...")
                } else {
                    LazyVGrid(columns: columns, spacing: 8) {
                        ForEach(AppointmentBookingPresentation.weekdaySymbols(), id: \.self) { symbol in
                            Text(symbol)
                                .font(.caption.weight(.semibold))
                                .foregroundStyle(.secondary)
                                .frame(maxWidth: .infinity)
                        }

                        ForEach(AppointmentBookingPresentation.monthGridDays(for: visibleMonth), id: \.self) { day in
                            calendarDayButton(day)
                        }
                    }
                }

                if let selectedDate {
                    Text("Jour sélectionné : \(spokenDayFormatter.string(from: selectedDate))")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                } else {
                    Text("Choisissez un jour réellement disponible dans le calendrier.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }

                Button("Étape précédente") {
                    onBack()
                }
                .buttonStyle(.bordered)
            }
            .padding(.vertical, 4)
        }
    }

    private var calendarToolbar: some View {
        HStack(spacing: 8) {
            Button(action: onPreviousMonth) {
                Image(systemName: "chevron.left")
            }
            .buttonStyle(.bordered)

            Button("Aujourd’hui", action: onCurrentMonth)
                .buttonStyle(.bordered)

            Button(action: onNextMonth) {
                Image(systemName: "chevron.right")
            }
            .buttonStyle(.bordered)
        }
    }

    @ViewBuilder
    private func calendarDayButton(_ day: Date) -> some View {
        let isCurrentMonth = AppointmentBookingPresentation.isSameMonth(day, visibleMonth)
        let isPast = AppointmentBookingPresentation.isPastDay(day)
        let isAvailable = availableDays.contains(where: { AppointmentBookingPresentation.isSameDay($0, day) })
        let isSelected = selectedDate.map { AppointmentBookingPresentation.isSameDay($0, day) } ?? false
        let isDisabled = !isCurrentMonth || isPast || !isAvailable

        Button {
            onSelectDay(day)
        } label: {
            Text("\(Calendar(identifier: .gregorian).component(.day, from: day))")
                .font(.subheadline.weight(.semibold))
                .frame(maxWidth: .infinity, minHeight: 38)
                .background(
                    RoundedRectangle(cornerRadius: 10)
                        .fill(isSelected ? Color.blue : (isAvailable && isCurrentMonth && !isPast ? Color.blue.opacity(0.12) : Color.gray.opacity(0.08)))
                )
                .foregroundStyle(isSelected ? .white : (isDisabled ? .secondary : .primary))
                .overlay(
                    RoundedRectangle(cornerRadius: 10)
                        .stroke(isAvailable && !isSelected && isCurrentMonth && !isPast ? Color.blue.opacity(0.3) : Color.clear, lineWidth: 1)
                )
        }
        .buttonStyle(.plain)
        .disabled(isDisabled)
        .accessibilityLabel(accessibilityLabel(for: day, isAvailable: isAvailable, isDisabled: isDisabled, isSelected: isSelected))
    }

    private func accessibilityLabel(for day: Date, isAvailable: Bool, isDisabled: Bool, isSelected: Bool) -> String {
        let base = spokenDayFormatter.string(from: day)
        let availability = isDisabled ? "indisponible" : (isAvailable ? "créneaux disponibles" : "aucun créneau")
        let selection = isSelected ? ", sélectionné" : ""
        return "\(base), \(availability)\(selection)"
    }
}

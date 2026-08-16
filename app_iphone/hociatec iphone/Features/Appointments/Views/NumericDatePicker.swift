import SwiftUI

struct NumericDatePicker: View {
    @Binding var date: Date
    var minimumDate: Date = Calendar(identifier: .gregorian).startOfDay(for: Date())
    var maximumDate: Date? = nil
    var calendar: Calendar = Calendar(identifier: .gregorian)

    @FocusState private var focusedField: Field?
    @State private var dayText = ""
    @State private var monthText = ""
    @State private var yearText = ""

    private enum Field {
        case day
        case month
        case year
    }

    private var presentation: NumericDatePickerPresentation {
        NumericDatePickerPresentation(
            date: date,
            minimumDate: minimumDate,
            maximumDate: maximumDate,
            calendar: calendar,
            year: parsedYear ?? calendar.component(.year, from: date),
            month: parsedMonth ?? calendar.component(.month, from: date)
        )
    }

    var body: some View {
        ViewThatFits(in: .horizontal) {
            horizontalLayout
            verticalLayout
        }
        .frame(maxWidth: .infinity)
        .toolbar {
            ToolbarItemGroup(placement: .keyboard) {
                Spacer()
                Button("Valider") {
                    commitInput()
                    focusedField = nil
                }
            }
        }
        .onAppear {
            clampDateToBounds()
            syncFromDate()
        }
        .onChangeCompat(date) { _ in
            clampDateToBounds()
            syncFromDate()
        }
        .onChangeCompat(dayText) { newValue in
            update(field: .day, with: newValue)
        }
        .onChangeCompat(monthText) { newValue in
            update(field: .month, with: newValue)
        }
        .onChangeCompat(yearText) { newValue in
            update(field: .year, with: newValue)
        }
        .onChangeCompat(focusedField) { newValue in
            if newValue == nil {
                commitInput()
            }
        }
    }

    private var horizontalLayout: some View {
        HStack(spacing: 8) {
            dateField(title: "Jour", text: $dayText, placeholder: "JJ", field: .day)
            separator
            dateField(title: "Mois", text: $monthText, placeholder: "MM", field: .month)
            separator
            dateField(title: "Année", text: $yearText, placeholder: "AAAA", field: .year)
        }
    }

    private var verticalLayout: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack(spacing: 8) {
                dateField(title: "Jour", text: $dayText, placeholder: "JJ", field: .day)
                dateField(title: "Mois", text: $monthText, placeholder: "MM", field: .month)
            }
            dateField(title: "Année", text: $yearText, placeholder: "AAAA", field: .year)
        }
    }

    private var separator: some View {
        Text("/")
            .font(.headline)
            .foregroundStyle(.tertiary)
            .padding(.top, 20)
    }

    @ViewBuilder
    private func dateField(title: String, text: Binding<String>, placeholder: String, field: Field) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption.weight(.semibold))
                .foregroundStyle(.secondary)

            TextField(placeholder, text: text)
                .keyboardType(.numberPad)
                .textInputAutocapitalization(.never)
                .multilineTextAlignment(.center)
                .font(.body.monospacedDigit())
                .padding(.vertical, 12)
                .padding(.horizontal, 8)
                .background(
                    RoundedRectangle(cornerRadius: 10)
                        .fill(Color(.secondarySystemBackground))
                )
                .overlay(
                    RoundedRectangle(cornerRadius: 10)
                        .stroke(focusedField == field ? Color.teal : Color(.separator), lineWidth: 1)
                )
                .focused($focusedField, equals: field)
        }
        .frame(maxWidth: field == .year ? 104 : 78)
    }

    private var parsedDay: Int? { Int(dayText) }
    private var parsedMonth: Int? { Int(monthText) }
    private var parsedYear: Int? { Int(yearText) }

    private func clampDateToBounds() {
        if date < minimumDate {
            date = minimumDate
        } else if let maximumDate, date > maximumDate {
            date = maximumDate
        }
    }

    private func syncFromDate() {
        dayText = String(format: "%02d", calendar.component(.day, from: date))
        monthText = String(format: "%02d", calendar.component(.month, from: date))
        yearText = String(format: "%04d", calendar.component(.year, from: date))
    }

    private func update(field: Field, with value: String) {
        let sanitized = sanitize(value, for: field)
        if sanitized != value {
            setText(sanitized, for: field)
            return
        }

        autoAdvance(after: field)
        applyIfPossible()
    }

    private func sanitize(_ value: String, for field: Field) -> String {
        let digits = value.filter(\.isNumber)
        let maxLength: Int
        switch field {
        case .day, .month:
            maxLength = 2
        case .year:
            maxLength = 4
        }
        return String(digits.prefix(maxLength))
    }

    private func setText(_ value: String, for field: Field) {
        switch field {
        case .day:
            dayText = value
        case .month:
            monthText = value
        case .year:
            yearText = value
        }
    }

    private func autoAdvance(after field: Field) {
        switch field {
        case .day where dayText.count == 2:
            focusedField = .month
        case .month where monthText.count == 2:
            focusedField = .year
        default:
            break
        }
    }

    private func applyIfPossible() {
        guard dayText.count == 2, monthText.count == 2, yearText.count == 4 else { return }
        guard let day = parsedDay, let month = parsedMonth, let year = parsedYear else { return }

        let clamped = presentation.clampedComponents(year: year, month: month, day: day)
        guard let newDate = presentation.date(
            year: clamped.year ?? year,
            month: clamped.month ?? month,
            day: clamped.day ?? day
        ) else { return }

        setDate(newDate)
    }

    private func commitInput() {
        guard let day = parsedDay, let month = parsedMonth, let year = parsedYear else {
            syncFromDate()
            return
        }

        let clamped = presentation.clampedComponents(year: year, month: month, day: day)
        guard let newDate = presentation.date(
            year: clamped.year ?? year,
            month: clamped.month ?? month,
            day: clamped.day ?? day
        ) else {
            syncFromDate()
            return
        }

        setDate(newDate)
    }

    private func setDate(_ newDate: Date) {
        let boundedDate: Date
        if let maximumDate, newDate > maximumDate {
            boundedDate = maximumDate
        } else if newDate < minimumDate {
            boundedDate = minimumDate
        } else {
            boundedDate = newDate
        }

        if boundedDate != date {
            date = boundedDate
        }
        syncFromDate()
    }
}

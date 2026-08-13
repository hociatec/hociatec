import SwiftUI

struct NumericDatePicker: View {
    @Binding var date: Date
    var minimumDate: Date = Calendar(identifier: .gregorian).startOfDay(for: Date())
    var maximumDate: Date? = nil
    var calendar: Calendar = Calendar(identifier: .gregorian)

    @State private var day: Int = 1
    @State private var month: Int = 1
    @State private var year: Int = Calendar(identifier: .gregorian).component(.year, from: Date())

    private var presentation: NumericDatePickerPresentation {
        NumericDatePickerPresentation(
            date: date,
            minimumDate: minimumDate,
            maximumDate: maximumDate,
            calendar: calendar,
            year: year,
            month: month
        )
    }

    var body: some View {
        HStack(spacing: 8) {
            Picker("Jour", selection: $day) {
                ForEach(presentation.dayValues, id: \.self) { value in
                    Text(String(format: "%02d", value)).tag(value)
                }
            }
            .pickerStyle(.wheel)

            Text("/")
                .foregroundStyle(.secondary)

            Picker("Mois", selection: $month) {
                ForEach(presentation.monthValues, id: \.self) { value in
                    Text(String(format: "%02d", value)).tag(value)
                }
            }
            .pickerStyle(.wheel)

            Text("/")
                .foregroundStyle(.secondary)

            Picker("Année", selection: $year) {
                ForEach(presentation.yearValues, id: \.self) { value in
                    Text(String(format: "%04d", value)).tag(value)
                }
            }
            .pickerStyle(.wheel)
        }
        .frame(maxWidth: .infinity)
        .onAppear {
            clampDateToBounds()
            syncFromDate()
            clampComponents()
            syncToDate()
        }
        .onChangeCompat(date) { _ in
            clampDateToBounds()
            syncFromDate()
            clampComponents()
        }
        .onChangeCompat(year) { _ in
            clampComponents()
            syncToDate()
        }
        .onChangeCompat(month) { _ in
            clampComponents()
            syncToDate()
        }
        .onChangeCompat(day) { _ in
            clampComponents()
            syncToDate()
        }
    }

    private func clampDateToBounds() {
        if date < minimumDate {
            date = minimumDate
        } else if let maximumDate, date > maximumDate {
            date = maximumDate
        }
    }

    private func clampComponents() {
        let clamped = presentation.clampedComponents(year: year, month: month, day: day)
        year = clamped.year
        month = clamped.month
        day = clamped.day
    }

    private func syncFromDate() {
        day = calendar.component(.day, from: date)
        month = calendar.component(.month, from: date)
        year = calendar.component(.year, from: date)
    }

    private func syncToDate() {
        guard let newDate = presentation.date(year: year, month: month, day: day) else { return }

        if let maximumDate, newDate > maximumDate {
            date = maximumDate
            syncFromDate()
            return
        }

        if newDate < minimumDate {
            date = minimumDate
            syncFromDate()
            return
        }

        if newDate != date {
            date = newDate
        }
    }
}

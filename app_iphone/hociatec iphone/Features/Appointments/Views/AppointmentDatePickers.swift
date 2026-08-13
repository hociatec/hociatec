import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

#if canImport(UIKit)
struct LocalizedDatePicker: UIViewRepresentable {
    @Binding var date: Date
    var displayedComponents: DatePickerComponents = [.date]
    var locale: Locale = Locale(identifier: "fr_FR")
    var calendar: Calendar = Calendar(identifier: .gregorian)
    var minimumDate: Date? = nil
    var maximumDate: Date? = nil
    var style: UIDatePickerStyle = .compact

    func makeUIView(context: Context) -> UIDatePicker {
        let picker = UIDatePicker()
        picker.datePickerMode = mode
        picker.preferredDatePickerStyle = style
        picker.locale = locale
        picker.calendar = calendar
        picker.date = date
        picker.minimumDate = minimumDate
        picker.maximumDate = maximumDate
        picker.addTarget(context.coordinator, action: #selector(Coordinator.changed(_:)), for: .valueChanged)
        return picker
    }

    func updateUIView(_ picker: UIDatePicker, context: Context) {
        if picker.date != date { picker.setDate(date, animated: false) }
        if picker.locale != locale { picker.locale = locale }
        if picker.calendar != calendar { picker.calendar = calendar }
        if picker.preferredDatePickerStyle != style { picker.preferredDatePickerStyle = style }
        picker.minimumDate = minimumDate
        picker.maximumDate = maximumDate
    }

    func makeCoordinator() -> Coordinator { Coordinator(self) }

    private var mode: UIDatePicker.Mode {
        switch (displayedComponents.contains(.date), displayedComponents.contains(.hourAndMinute)) {
        case (true, true): return .dateAndTime
        case (true, false): return .date
        case (false, true): return .time
        default: return .date
        }
    }

    final class Coordinator: NSObject {
        var parent: LocalizedDatePicker
        init(_ parent: LocalizedDatePicker) { self.parent = parent }
        @objc func changed(_ sender: UIDatePicker) {
            parent.date = sender.date
        }
    }
}
#endif

struct NumericDatePicker: View {
    @Binding var date: Date
    var minimumDate: Date = Calendar(identifier: .gregorian).startOfDay(for: Date())
    var maximumDate: Date? = nil
    var calendar: Calendar = Calendar(identifier: .gregorian)

    @State private var day: Int = 1
    @State private var month: Int = 1
    @State private var year: Int = Calendar(identifier: .gregorian).component(.year, from: Date())

    private var minYear: Int { calendar.component(.year, from: minimumDate) }
    private var minMonth: Int { calendar.component(.month, from: minimumDate) }
    private var minDay: Int { calendar.component(.day, from: minimumDate) }

    private var currentYear: Int { calendar.component(.year, from: Date()) }
    private var maxYear: Int {
        if let maximumDate { return calendar.component(.year, from: maximumDate) }
        return currentYear + 10
    }

    private var yearRange: ClosedRange<Int> { minYear...maxYear }
    private var monthStart: Int { year == minYear ? minMonth : 1 }
    private var monthEnd: Int { 12 }
    private var dayStart: Int { (year == minYear && month == minMonth) ? minDay : 1 }

    private var dayEnd: Int {
        var components = DateComponents()
        components.year = year
        components.month = month
        let monthDate = calendar.date(from: components) ?? Date()
        return calendar.range(of: .day, in: .month, for: monthDate)?.count ?? 31
    }

    var body: some View {
        HStack(spacing: 8) {
            Picker("Jour", selection: $day) {
                ForEach(Array(dayStart...dayEnd), id: \.self) { value in
                    Text(String(format: "%02d", value)).tag(value)
                }
            }
            .pickerStyle(.wheel)

            Text("/")
                .foregroundStyle(.secondary)

            Picker("Mois", selection: $month) {
                ForEach(Array(monthStart...monthEnd), id: \.self) { value in
                    Text(String(format: "%02d", value)).tag(value)
                }
            }
            .pickerStyle(.wheel)

            Text("/")
                .foregroundStyle(.secondary)

            Picker("Année", selection: $year) {
                ForEach(Array(yearRange), id: \.self) { value in
                    Text(String(format: "%04d", value)).tag(value)
                }
            }
            .pickerStyle(.wheel)
        }
        .frame(maxWidth: .infinity)
        .onAppear {
            if date < minimumDate {
                date = minimumDate
            }
            syncFromDate()
            clampComponentsToMinimum()
            syncToDate()
        }
        .onChangeCompat(date) { newValue in
            if newValue < minimumDate {
                date = minimumDate
            }
            syncFromDate()
            clampComponentsToMinimum()
        }
        .onChangeCompat(year) { _ in
            clampComponentsToMinimum()
            syncToDate()
        }
        .onChangeCompat(month) { _ in
            clampComponentsToMinimum()
            syncToDate()
        }
        .onChangeCompat(day) { _ in
            clampComponentsToMinimum()
            syncToDate()
        }
    }

    private func clampComponentsToMinimum() {
        if year < minYear { year = minYear }
        if year == minYear && month < minMonth { month = minMonth }
        if year == minYear && month == minMonth && day < minDay { day = minDay }
        if day > dayEnd { day = dayEnd }
    }

    private func syncFromDate() {
        day = calendar.component(.day, from: date)
        month = calendar.component(.month, from: date)
        year = calendar.component(.year, from: date)
    }

    private func syncToDate() {
        var components = DateComponents()
        components.calendar = calendar
        components.year = year
        components.month = month
        components.day = day
        if let newDate = components.date {
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
}

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

        init(_ parent: LocalizedDatePicker) {
            self.parent = parent
        }

        @objc func changed(_ sender: UIDatePicker) {
            parent.date = sender.date
        }
    }
}
#endif

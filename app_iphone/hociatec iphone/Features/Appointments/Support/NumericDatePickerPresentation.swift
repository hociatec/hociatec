import Foundation

struct NumericDatePickerPresentation {
    let date: Date
    let minimumDate: Date
    let maximumDate: Date?
    let calendar: Calendar
    let year: Int
    let month: Int

    var minYear: Int { calendar.component(.year, from: minimumDate) }
    var minMonth: Int { calendar.component(.month, from: minimumDate) }
    var minDay: Int { calendar.component(.day, from: minimumDate) }

    var currentYear: Int { calendar.component(.year, from: Date()) }
    var maxYear: Int {
        if let maximumDate {
            return calendar.component(.year, from: maximumDate)
        }
        return currentYear + 10
    }

    var yearValues: [Int] {
        Array(minYear...maxYear)
    }

    var monthStart: Int {
        year == minYear ? minMonth : 1
    }

    var monthValues: [Int] {
        Array(monthStart...12)
    }

    var dayStart: Int {
        (year == minYear && month == minMonth) ? minDay : 1
    }

    var dayEnd: Int {
        var components = DateComponents()
        components.year = year
        components.month = month
        let monthDate = calendar.date(from: components) ?? date
        return calendar.range(of: .day, in: .month, for: monthDate)?.count ?? 31
    }

    var dayValues: [Int] {
        Array(dayStart...dayEnd)
    }

    func clampedComponents(year: Int, month: Int, day: Int) -> DateComponents {
        var resultYear = year
        var resultMonth = month
        var resultDay = day

        if resultYear < minYear { resultYear = minYear }
        if resultYear == minYear && resultMonth < minMonth { resultMonth = minMonth }

        let minAllowedDay = (resultYear == minYear && resultMonth == minMonth) ? minDay : 1
        if resultDay < minAllowedDay { resultDay = minAllowedDay }

        let maxAllowedDay = monthDayCount(year: resultYear, month: resultMonth)
        if resultDay > maxAllowedDay { resultDay = maxAllowedDay }

        var components = DateComponents()
        components.year = resultYear
        components.month = resultMonth
        components.day = resultDay
        return components
    }

    func date(year: Int, month: Int, day: Int) -> Date? {
        var components = DateComponents()
        components.calendar = calendar
        components.year = year
        components.month = month
        components.day = day
        return components.date
    }

    private func monthDayCount(year: Int, month: Int) -> Int {
        var components = DateComponents()
        components.year = year
        components.month = month
        let monthDate = calendar.date(from: components) ?? date
        return calendar.range(of: .day, in: .month, for: monthDate)?.count ?? 31
    }
}

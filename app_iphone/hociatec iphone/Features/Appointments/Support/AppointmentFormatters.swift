import Foundation

let timeFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.timeStyle = .short
    formatter.dateStyle = .none
    return formatter
}()

let dayFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateFormat = "dd/MM/yyyy"
    return formatter
}()

let spokenDayFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .full
    formatter.timeStyle = .none
    return formatter
}()

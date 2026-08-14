import Foundation

struct AppointmentPrestation: Decodable, Identifiable {
    let id: Int
    let name: String
    let durationMinutes: Int
    let priceCents: Int
}

struct AppointmentPrestationList: Decodable {
    let items: [AppointmentPrestation]
}

struct AppointmentSlot: Decodable, Identifiable {
    var id: String { "\(startAt.timeIntervalSince1970)" }
    let startAt: Date
    let endAt: Date

    private enum CodingKeys: String, CodingKey {
        case startAt = "start"
        case endAt = "end"
    }
}

struct AppointmentAvailability: Decodable {
    let slots: [AppointmentSlot]
}

struct AppointmentSummary: Decodable, Identifiable {
    let id: Int
    let startAt: Date
    let endAt: Date
    let status: String?
    let statusCode: String?
    let isCancelable: Bool?
    let isReschedulable: Bool?
    let prestation: AppointmentPrestation
}

extension AppointmentSummary {
    var isCancelledStatus: Bool {
        let raw = (statusCode ?? status ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let normalized = raw.folding(options: .diacriticInsensitive, locale: .current).lowercased()
        return normalized.contains("cancel") || normalized.contains("annul")
    }

    var canCancel: Bool {
        if let isCancelable {
            return isCancelable
        }
        if isCancelledStatus { return false }
        let raw = (statusCode ?? status ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let normalized = raw.folding(options: .diacriticInsensitive, locale: .current).lowercased()
        let isConfirmed = normalized == "confirmed" || normalized.contains("conf")
        return isConfirmed && startAt > Date()
    }

    var canReschedule: Bool {
        if let isReschedulable {
            return isReschedulable
        }

        return !isCancelledStatus && startAt > Date()
    }
}

struct AppointmentList: Decodable {
    let upcoming: [AppointmentSummary]
    let past: [AppointmentSummary]
}

struct AppointmentData: Decodable {
    let appointment: AppointmentSummary
}

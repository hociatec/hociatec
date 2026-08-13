import Foundation

struct TrainingCategoryReference: Decodable {
    let id: Int?
    let name: String
    let slug: String
}

struct TrainingFormatOption: Decodable {
    let value: String
    let label: String
}

struct TrainingCategory: Decodable, Identifiable, Equatable {
    let id: Int
    let name: String
    let slug: String
    let position: Int
    let isActive: Bool
}

struct TrainingRoadmapItem: Decodable, Identifiable {
    let id: Int
    let position: Int
    let title: String
}

struct Training: Decodable, Identifiable {
    let id: Int
    let title: String
    let slug: String
    let shortDescription: String?
    let objective: String?
    let audience: String?
    let category: String
    let durationMinutes: Int
    let priceCents: Int
    let availableFormats: [String]
    let isActive: Bool
    let roadmap: [TrainingRoadmapItem]
    let categoryDetails: TrainingCategoryReference?
    let availableFormatDetails: [TrainingFormatOption]
}

struct TrainingSession: Decodable, Identifiable {
    let id: Int
    let format: String
    let formatLabel: String
    let startsAt: Date
    let endsAt: Date
    let dailyStartTime: String
    let dailyEndTime: String
    let includeWeekends: Bool
    let location: String?
    let meetingUrl: String?
    let capacity: Int
    let enrolledCount: Int
    let remainingSeats: Int
    let status: String
    let statusLabel: String
}

struct TrainingListData: Decodable {
    let items: [Training]
    let meta: PaginationMeta
}

struct TrainingCategoryListData: Decodable {
    let items: [TrainingCategory]
}

struct TrainingDetailData: Decodable {
    let training: Training
    let sessions: [TrainingSession]
}

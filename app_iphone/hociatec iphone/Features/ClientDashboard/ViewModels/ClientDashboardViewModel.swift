import Foundation
import Combine

@MainActor
final class ClientDashboardViewModel: ObservableObject {
    @Published var isLoading = false
    @Published var error: String?
    @Published var partialError = false
    @Published var actions: [ClientDashboardAction] = []
    @Published var loyalty = LoyaltyBalance(points: 0, euroCents: 0, pointsPerEuroEarned: 10, pointsPerEuroConverted: 100)
    @Published var convertPoints = ""
    @Published var conversionMessage: String?

    let quoteService: QuoteServing
    let appointmentService: AppointmentServing
    let orderService: OrderServing
    let trainingService: TrainingServing
    let workspaceService: WorkspaceServing
    let actionBuilder = ClientDashboardActionBuilder()

    init(
        quoteService: QuoteServing,
        appointmentService: AppointmentServing,
        orderService: OrderServing,
        trainingService: TrainingServing,
        workspaceService: WorkspaceServing
    ) {
        self.quoteService = quoteService
        self.appointmentService = appointmentService
        self.orderService = orderService
        self.trainingService = trainingService
        self.workspaceService = workspaceService
    }
}

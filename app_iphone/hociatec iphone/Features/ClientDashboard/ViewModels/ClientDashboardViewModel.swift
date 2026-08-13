import Foundation

@MainActor
final class ClientDashboardViewModel: ObservableObject {
    @Published var isLoading = false
    @Published var error: String?
    @Published var partialError = false
    @Published var actions: [ClientDashboardAction] = []
    @Published var loyalty = LoyaltyBalance(points: 0, euroCents: 0, pointsPerEuroEarned: 10, pointsPerEuroConverted: 100)
    @Published var convertPoints = ""
    @Published var conversionMessage: String?

    private let quoteService: QuoteServing
    private let appointmentService: AppointmentServing
    private let orderService: OrderServing
    private let trainingService: TrainingServing
    private let workspaceService: WorkspaceServing
    private let actionBuilder = ClientDashboardActionBuilder()

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

import Foundation

extension ClientDashboardViewModel {
    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        partialError = false

        async let quotesResult = capture { try await self.quoteService.myQuotes() }
        async let appointmentsResult = capture { try await self.appointmentService.myAppointments() }
        async let pendingReviewsResult = capture { try await self.orderService.pendingReviews() }
        async let loyaltyResult = capture { try await self.workspaceService.loyaltyBalance() }
        async let trainingsResult = capture { try await self.trainingService.myEnrollments(page: 1, perPage: 10) }

        let quotes = await quotesResult
        let appointments = await appointmentsResult
        let pendingReviews = await pendingReviewsResult
        let loyaltyResponse = await loyaltyResult
        let trainings = await trainingsResult
        guard requestID == loadRequestID else { return }

        let failures = [
            quotes.failure,
            appointments.failure,
            pendingReviews.failure,
            loyaltyResponse.failure,
            trainings.failure
        ].compactMap { $0 }

        let successfulLoads = [
            quotes.value != nil,
            appointments.value != nil,
            pendingReviews.value != nil,
            loyaltyResponse.value != nil,
            trainings.value != nil
        ].filter { $0 }.count

        if successfulLoads == 0, let firstFailure = failures.first {
            error = firstFailure.localizedDescription
        }
        partialError = !failures.isEmpty && successfulLoads > 0

        if let loyaltyValue = loyaltyResponse.value {
            self.loyalty = loyaltyValue
            syncConvertPointsIfNeeded()
        }

        actions = actionBuilder.makeActions(
            quotes: quotes.value ?? [],
            appointments: appointments.value,
            pendingReviews: pendingReviews.value ?? [],
            trainings: trainings.value?.items ?? []
        )
        hasLoadedOnce = successfulLoads > 0

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    private func capture<T>(_ operation: @escaping () async throws -> T) async -> ClientDashboardLoadResult<T> {
        do {
            return ClientDashboardLoadResult(value: try await operation(), failure: nil)
        } catch {
            if isBenignCancellation(error) {
                return ClientDashboardLoadResult(value: nil, failure: nil)
            }
            return ClientDashboardLoadResult(value: nil, failure: error)
        }
    }

    private func isBenignCancellation(_ error: Error) -> Bool {
        error.isBenignCancellation
    }
}

private struct ClientDashboardLoadResult<T> {
    let value: T?
    let failure: Error?
}

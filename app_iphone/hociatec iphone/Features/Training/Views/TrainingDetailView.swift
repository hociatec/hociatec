import SwiftUI

struct TrainingDetailView: View {
    @StateObject private var viewModel: TrainingDetailViewModel

    init(api: TrainingServing, slug: String) {
        _viewModel = StateObject(wrappedValue: TrainingDetailViewModel(service: api, slug: slug))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.training == nil {
                TrainingDetailLoadingSection()
            } else if let training = viewModel.training {
                TrainingDetailHeroSection(training: training)
                TrainingRoadmapSection(training: training)
                TrainingSessionsSection(training: training, viewModel: viewModel)
            }
        }
        .navigationTitle(viewModel.training?.title ?? "Formation")
        .task { await viewModel.load() }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.statusMessage)
    }
}

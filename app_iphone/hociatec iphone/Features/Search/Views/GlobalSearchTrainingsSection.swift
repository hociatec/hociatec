import SwiftUI

struct GlobalSearchTrainingsSection: View {
    @EnvironmentObject private var container: AppContainer

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.trainings.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat formation.")
            } else {
                ForEach(viewModel.trainings) { training in
                    NavigationLink {
                        TrainingDetailView(api: container.services.training, slug: training.slug)
                    } label: {
                        GlobalSearchTrainingRow(training: training)
                    }
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Formations", total: viewModel.trainingTotal, query: viewModel.query) {
                TrainingsCatalogView(
                    api: container.services.training,
                    initialSearch: viewModel.query
                )
            }
        }
    }
}

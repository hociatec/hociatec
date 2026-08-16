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
                    VStack(alignment: .leading, spacing: 8) {
                        GlobalSearchTrainingRow(training: training)
                        NavigationLink {
                            TrainingDetailView(api: container.services.training, slug: training.slug)
                        } label: {
                            Label("Voir la formation", systemImage: "arrow.right.circle")
                                .font(.footnote.weight(.semibold))
                        }
                        .buttonStyle(.borderless)
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
        } footer: {
            GlobalSearchPaginationSection(viewModel: viewModel, filter: .trainings)
        }
    }
}

import SwiftUI

struct GlobalSearchResultsView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Group {
            if viewModel.totalResults == 0 {
                Section("Résultats") {
                    GlobalSearchEmptyRow(message: "Aucun résultat pour cette recherche.")
                }
            }

            if viewModel.shouldShow(.products) && viewModel.hasVisibleResults(for: .products) {
                GlobalSearchProductsSection(viewModel: viewModel)
                    .environmentObject(container)
                    .environmentObject(cart)
            }

            if viewModel.shouldShow(.services) && viewModel.hasVisibleResults(for: .services) {
                GlobalSearchServicesSection(viewModel: viewModel)
                    .environmentObject(container)
            }

            if viewModel.shouldShow(.trainings) && viewModel.hasVisibleResults(for: .trainings) {
                GlobalSearchTrainingsSection(viewModel: viewModel)
                    .environmentObject(container)
            }

            if viewModel.shouldShow(.news) && viewModel.hasVisibleResults(for: .news) {
                GlobalSearchNewsSection(viewModel: viewModel)
                    .environmentObject(container)
            }
        }
    }
}

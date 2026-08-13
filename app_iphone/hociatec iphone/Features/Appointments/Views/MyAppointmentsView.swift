import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct MyAppointmentsView: View {
    private let service: AppointmentServing
    @StateObject private var viewModel: MyAppointmentsViewModel

    @State private var tab: AppointmentTabFilter = .upcoming

    init(service: AppointmentServing) {
        self.service = service
        _viewModel = StateObject(wrappedValue: MyAppointmentsViewModel(service: service))
    }

    var body: some View {
        List {
            if let error = viewModel.error {
                Section { Text(error).foregroundStyle(.red) }
            }

            Section {
                Picker("Afficher", selection: $tab) {
                    ForEach(AppointmentTabFilter.allCases) { filter in
                        Text(filter.label).tag(filter)
                    }
                }
                .pickerStyle(.segmented)
                .accessibilityLabel("Filtre des rendez-vous")
                .accessibilityValue(tab.label)
                .accessibilityHint("Sélectionnez pour filtrer la liste des rendez-vous")

                AppointmentSummaryHeader(
                    upcomingCount: upcomingFiltered.count,
                    pastCount: pastFiltered.count,
                    cancelledCount: cancelledAppointments.count,
                    isLoading: viewModel.isLoading
                )

                if tab == .upcoming, let next = nextUpcoming {
                    AppointmentCard(
                        appointment: next,
                        accentColor: .blue.opacity(0.15)
                    ) {
                        AppointmentDetailScreen(appointment: next, viewModel: viewModel)
                    }
                }
            }

            Section {
                if currentListFiltered.isEmpty {
                    if !(tab == .upcoming && nextUpcoming != nil) {
                        AppointmentEmptyState(
                            icon: "calendar.badge.exclamationmark",
                            message: emptyStateMessage
                        )
                        .frame(maxWidth: .infinity)
                        .listRowInsets(EdgeInsets())
                        .listRowSeparator(.hidden)

                        if tab == .upcoming {
                            NavigationLink {
                                AppointmentBookingView(service: service)
                            } label: {
                                Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
                                    .fontWeight(.semibold)
                            }
                        }
                    }
                } else {
                    ForEach(currentListFiltered) { appointment in
                        AppointmentCard(appointment: appointment) {
                            AppointmentDetailScreen(appointment: appointment, viewModel: viewModel)
                        }
                        .listRowInsets(EdgeInsets())
                        .listRowSeparator(.hidden)
                    }
                }
            }
        }
        .navigationTitle("Mes rendez-vous")
        .task { await viewModel.load(force: true) }
        .refreshable { await viewModel.load(force: true) }
        .overlay(alignment: .top) {
            if let message = viewModel.successMessage {
                AppointmentSuccessBanner(message: message)
                    .padding(.top, 8)
                    .onAppear {
#if canImport(UIKit)
                        UIAccessibility.post(notification: .announcement, argument: message)
#endif
                        DispatchQueue.main.asyncAfter(deadline: .now() + 2.0) {
                            if viewModel.successMessage == message { viewModel.successMessage = nil }
                        }
                    }
            }
        }
        .environment(\.locale, Locale(identifier: "fr_FR"))
    }

    private var upcomingFiltered: [AppointmentSummary] {
        viewModel.upcoming.filter { !$0.isCancelledStatus }.sorted { $0.startAt < $1.startAt }
    }

    private var pastFiltered: [AppointmentSummary] {
        viewModel.past.filter { !$0.isCancelledStatus }.sorted { $0.startAt > $1.startAt }
    }

    private var cancelledAppointments: [AppointmentSummary] {
        (viewModel.upcoming + viewModel.past)
            .filter(\.isCancelledStatus)
            .sorted { $0.startAt > $1.startAt }
    }

    private var nextUpcoming: AppointmentSummary? {
        upcomingFiltered.first
    }

    private var currentList: [AppointmentSummary] {
        switch tab {
        case .upcoming: return upcomingFiltered
        case .past: return pastFiltered
        case .cancelled: return cancelledAppointments
        }
    }

    private var currentListFiltered: [AppointmentSummary] {
        if tab == .upcoming, let next = nextUpcoming {
            return currentList.filter { $0.id != next.id }
        }
        return currentList
    }

    private var emptyStateMessage: String {
        switch tab {
        case .upcoming: return "Aucun rendez-vous à venir."
        case .past: return "Aucun rendez-vous passé."
        case .cancelled: return "Aucun rendez-vous annulé."
        }
    }
}

private enum AppointmentTabFilter: String, CaseIterable, Identifiable {
    case upcoming = "À venir"
    case past = "Passés"
    case cancelled = "Annulés"

    var id: String { rawValue }
    var label: String { rawValue }
}

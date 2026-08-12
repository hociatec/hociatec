import SwiftUI

@MainActor
struct ProductReviewsView: View {
    let productName: String
    let productSlug: String
    let productSku: String

    @EnvironmentObject private var container: AppContainer
    @State private var reviews: [Review] = []
    @State private var myReview: Review? = nil
    @State private var page: Int = 1
    @State private var perPage: Int = 20
    @State private var total: Int = 0
    @State private var average: Double? = nil
    @State private var isLoading = false
    @State private var error: String? = nil

    var body: some View {
        List {
            if let average, total > 0 {
                Section {
                    HStack(spacing: 10) {
                        ratingStars(for: average)
                        Text(String(format: "%.1f/5", average))
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                        Spacer()
                        Text("\(total) avis")
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                }
            }

            if let myReview {
                Section("Votre avis") {
                    VStack(alignment: .leading, spacing: 6) {
                        HStack {
                            ratingStars(for: Double(myReview.score))
                            Spacer()
                            Text(DateFormatters.frDay.string(from: myReview.createdAt))
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }
                        if let comment = myReview.comment, !comment.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                            Text(comment)
                        } else {
                            Text("Sans commentaire.")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                        }
                    }
                    .padding(.vertical, 4)
                }
            }

            if let error {
                Section { Text(error).foregroundStyle(.red) }
            }

            if isLoading && reviews.isEmpty {
                Section { ProgressView("Chargement des avis…") }
            } else if reviews.isEmpty && error == nil {
                Section {
                    let message: String = {
                        if total == 0 { return "Aucun avis pour l’instant." }
                        if container.account.isLoggedIn {
                            return myReview == nil ? "Aucun commentaire publié pour le moment." : "Aucun autre commentaire public pour le moment."
                        }
                        return "Connectez-vous pour voir les avis."
                    }()
                    Text(message).foregroundStyle(.secondary)
                }
            } else {
                Section {
                    ForEach(reviews, id: \.id) { review in
                        VStack(alignment: .leading, spacing: 6) {
                            HStack {
                                ratingStars(for: Double(review.score))
                                Spacer()
                                Text(DateFormatters.frDay.string(from: review.createdAt))
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            if let comment = review.comment, !comment.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                                Text(comment)
                            } else {
                                Text("Sans commentaire.")
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                            Text(review.author.displayName)
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                        }
                        .padding(.vertical, 6)
                    }
                }

                if canLoadMore {
                    Section {
                        Button {
                            Task { await loadMore() }
                        } label: {
                            if isLoading {
                                ProgressView().frame(maxWidth: .infinity)
                            } else {
                                Text("Charger plus")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity)
                            }
                        }
                        .disabled(isLoading)
                    }
                }
            }
        }
        .navigationTitle("Avis")
        .navigationBarTitleDisplayMode(.inline)
        .task { await load(page: 1, replace: true) }
        .onChangeCompat(container.account.isLoggedIn) { isLoggedIn in
            guard isLoggedIn else { return }
            Task { await load(page: 1, replace: true) }
        }
        .refreshable { await load(page: 1, replace: true) }
        .accessibilityLabel("Avis sur \(productName)")
    }

    private var canLoadMore: Bool {
        !isLoading && reviews.count < total
    }

    private func loadMore() async {
        guard !isLoading else { return }
        guard reviews.count < total else { return }
        await load(page: page + 1, replace: false)
    }

    private func load(page: Int, replace: Bool) async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await container.api.productReviews(slug: productSlug, page: page, perPage: perPage)
            self.page = data.meta.page
            self.perPage = data.meta.perPage
            self.total = data.meta.total
            self.average = data.meta.average
            if replace {
                self.reviews = data.items
            } else {
                self.reviews.append(contentsOf: data.items)
            }
            await loadMyReviewIfNeeded()
        } catch {
            self.error = error.localizedDescription
            if replace {
                self.reviews = []
                self.total = 0
                self.average = nil
            }
            await loadMyReviewIfNeeded()
        }
    }

    private func loadMyReviewIfNeeded() async {
        guard container.account.isLoggedIn else {
            myReview = nil
            return
        }

        // If public reviews already contain items, don't spend another request.
        if !reviews.isEmpty { return }

        do {
            let orders = try await container.api.myOrders()
            var candidates: [Review] = []
            for order in orders {
                for item in order.items where item.productSku == productSku {
                    if let review = item.review {
                        candidates.append(review)
                    }
                }
            }
            myReview = candidates.sorted(by: { $0.createdAt > $1.createdAt }).first
        } catch {
            // Keep silent: this is a best-effort enhancement.
            myReview = nil
        }
    }

    private func ratingStars(for average: Double) -> some View {
        HStack(spacing: 2) {
            ForEach(0..<5, id: \.self) { idx in
                let threshold = Double(idx + 1)
                Image(systemName: average >= threshold ? "star.fill" : (average > Double(idx) ? "star.leadinghalf.filled" : "star"))
                    .foregroundStyle(.yellow)
                    .accessibilityHidden(true)
            }
        }
        .accessibilityLabel(String(format: "Note %.1f sur 5", average))
    }
}

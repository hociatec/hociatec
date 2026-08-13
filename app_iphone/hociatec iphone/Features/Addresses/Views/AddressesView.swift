import SwiftUI

struct AddressesView: View {
    @ObservedObject var account: AccountViewModel
    @State private var draft = AddressCreateDraft()

    var body: some View {
        List {
            AddressesListSection(account: account)
            AddressCreateSection(account: account, draft: $draft)
            AddressesErrorSection(error: account.error)
        }
        .navigationTitle("Adresses")
    }
}

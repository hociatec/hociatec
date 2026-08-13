import SwiftUI

struct LegalMenuView: View {
    var body: some View {
        List {
            NavigationLink("CGU") {
                CGULegalView()
            }

            NavigationLink("CGV") {
                CGVLegalView()
            }

            NavigationLink("Confidentialité") {
                PrivacyLegalView()
            }

            NavigationLink("Mentions légales") {
                LegalMentionsView()
            }
        }
        .navigationTitle("Informations légales")
    }
}

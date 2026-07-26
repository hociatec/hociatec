export const ProductDescriptionSection = ({ description }: { description: string }) => (
  <section className="catalog-detail-content">
    <h2>Description du produit</h2>
    <p>{description}</p>
  </section>
);

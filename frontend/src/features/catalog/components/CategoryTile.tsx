import '../pages/CatalogPages.css';

import { Link } from 'react-router-dom';

import type { CatalogCategory } from '../api';

interface CategoryTileProps {
  category: CatalogCategory;
  onSelect?: (category: CatalogCategory) => void;
  href?: string;
}

export const CategoryTile = ({
  category,
  onSelect,
  href,
}: CategoryTileProps) => {
  const handleClick = () => {
    if (onSelect) {
      onSelect(category);
    }
  };

  const content = (
    <article className="catalog-category-tile" onClick={handleClick}>
      <header className="catalog-category-tile__header">
        <span className="catalog-category-tile__icon" aria-hidden="true">
          {category.name.charAt(0).toUpperCase()}
        </span>
        <div>
          <h3 className="catalog-category-tile__title">{category.name}</h3>
          {category.productsCount !== undefined && (
            <p className="catalog-category-tile__count">
              {category.productsCount} produit{category.productsCount > 1 ? 's' : ''}
            </p>
          )}
        </div>
      </header>
      {category.description && (
        <p className="catalog-category-tile__description">
          {category.description}
        </p>
      )}
    </article>
  );

  if (href) {
    return (
      <Link to={href} className="catalog-category-tile__link">
        {content}
      </Link>
    );
  }

  return content;
};

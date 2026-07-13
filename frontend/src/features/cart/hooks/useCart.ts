import { useContext } from 'react';

import { CartContext } from '@/features/cart/context/CartContext';

export const useCart = () => useContext(CartContext);

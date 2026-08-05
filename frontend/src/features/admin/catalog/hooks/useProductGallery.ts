import { useEffect, useRef, useState } from 'react';

import { GALLERY_SIZE } from '@/features/admin/catalog/utils/productFormConfig';

const emptyGallery = <T>() => Array.from({ length: GALLERY_SIZE }, () => null as T | null);

export const useProductGallery = () => {
  const [galleryFiles, setGalleryFiles] = useState<Array<File | null>>(emptyGallery<File>);
  const [galleryPreviews, setGalleryPreviews] = useState<Array<string | null>>(
    emptyGallery<string>,
  );
  const [initialGallery, setInitialGallery] = useState<Array<string | null>>(emptyGallery<string>);
  const [galleryToRemove, setGalleryToRemove] = useState<number[]>([]);
  const objectUrlsRef = useRef<Array<string | null>>(emptyGallery<string>());

  useEffect(
    () => () => {
      objectUrlsRef.current.forEach((url, index) => {
        if (url) URL.revokeObjectURL(url);
        objectUrlsRef.current[index] = null;
      });
    },
    [],
  );

  const hydrate = (gallery: Array<{ position: number; url: string }>) => {
    const populated = emptyGallery<string>();
    gallery.forEach((item) => {
      if (item.position >= 0 && item.position < GALLERY_SIZE) populated[item.position] = item.url;
    });
    setInitialGallery(populated);
    setGalleryPreviews(populated);
    setGalleryFiles(emptyGallery<File>());
    setGalleryToRemove([]);
  };

  const reset = () => {
    objectUrlsRef.current.forEach((url, index) => {
      if (url) URL.revokeObjectURL(url);
      objectUrlsRef.current[index] = null;
    });
    setGalleryFiles(emptyGallery<File>());
    setGalleryPreviews(emptyGallery<string>());
    setInitialGallery(emptyGallery<string>());
    setGalleryToRemove([]);
  };

  const onFileChange = (index: number, fileList: FileList | null) => {
    const file = fileList?.[0] ?? null;
    if (objectUrlsRef.current[index]) URL.revokeObjectURL(objectUrlsRef.current[index]!);
    objectUrlsRef.current[index] = null;

    setGalleryFiles((previous) =>
      previous.map((value, itemIndex) => (itemIndex === index ? file : value)),
    );
    if (file) {
      const objectUrl = URL.createObjectURL(file);
      objectUrlsRef.current[index] = objectUrl;
      setGalleryPreviews((previous) =>
        previous.map((value, itemIndex) => (itemIndex === index ? objectUrl : value)),
      );
      setGalleryToRemove((previous) => previous.filter((value) => value !== index));
      return;
    }

    const fallback = initialGallery[index] ?? null;
    setGalleryPreviews((previous) =>
      previous.map((value, itemIndex) => (itemIndex === index ? fallback : value)),
    );
    if (!fallback) setGalleryToRemove((previous) => previous.filter((value) => value !== index));
  };

  const remove = (index: number) => {
    if (objectUrlsRef.current[index]) URL.revokeObjectURL(objectUrlsRef.current[index]!);
    objectUrlsRef.current[index] = null;
    setGalleryFiles((previous) =>
      previous.map((value, itemIndex) => (itemIndex === index ? null : value)),
    );
    setGalleryPreviews((previous) =>
      previous.map((value, itemIndex) => (itemIndex === index ? null : value)),
    );
    setGalleryToRemove((previous) =>
      initialGallery[index]
        ? Array.from(new Set([...previous, index]))
        : previous.filter((value) => value !== index),
    );
  };

  return {
    galleryFiles,
    galleryPreviews,
    initialGallery,
    galleryToRemove,
    hydrate,
    reset,
    onFileChange,
    remove,
  };
};

const isBase64 = (str) => {
  if (typeof str !== 'string') {
    return false;
  }

  const cleaned = str.trim().replace(/\s/g, '');

  if (! cleaned || cleaned.length % 4 !== 0 || cleaned.length < 20) {
    return false;
  }

  return /^[A-Za-z0-9+/]+={0,2}$/.test(cleaned);
};

const fileToBase64 = (file) =>
  new Promise((resolve, reject) => {
    if (! file || ! (file instanceof Blob)) {
      reject(new Error('not a file'));
      return;
    }

    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
  });

const b64File = async (file) => ({
  name: file.name,
  mime: file.type,
  size: file.size,
  extension: file.name ? file.name.split('.').pop() : '',
  base64: await fileToBase64(file),
  id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
});

export const useFileManager = () => ({
  fileToBase64,
  b64File,
  isBase64,
});

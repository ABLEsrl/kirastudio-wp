import { reactive } from 'vue';

export const useSequentialUploader = () => {
  const filesProgress = reactive({});

  const updateFileProgress = (fileId, progress) => {
    if (filesProgress[fileId]) {
      filesProgress[fileId].progress = progress;
    }
  };

  const uploadFilesSequentially = async (files, uploadFn) => {
    files.forEach((file) => {
      const id = file.id || file.name;
      filesProgress[id] = {
        uploading: false,
        progress: 0,
        completed: false,
        error: null,
      };
    });

    const results = [];

    for (const file of files) {
      const id = file.id || file.name;
      filesProgress[id].uploading = true;

      try {
        const result = await uploadFn(file, (progress) => updateFileProgress(id, progress));
        filesProgress[id].completed = true;
        filesProgress[id].progress = 100;
        results.push({ file, result, success: true });
      } catch (error) {
        filesProgress[id].error = error;
        results.push({ file, error, success: false });
      } finally {
        filesProgress[id].uploading = false;
      }
    }

    return results;
  };

  const reset = () => {
    Object.keys(filesProgress).forEach((key) => delete filesProgress[key]);
  };

  const getFileProgress = (fileId) =>
    filesProgress[fileId] || {
      uploading: false,
      progress: 0,
      completed: false,
      error: null,
    };

  return {
    filesProgress,
    uploadFilesSequentially,
    reset,
    getFileProgress,
  };
};

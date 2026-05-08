export const Mimes = {
  list: [
    { name: 'image', ext: ['bmp', 'jpg', 'jpeg', 'png', 'gif', 'tiff'], md: '$mdiFileImage' },
    { name: 'doc', ext: ['doc', 'docx', 'pages', 'rtf', 'txt'], md: '$mdiFileDocument' },
    { name: 'data', ext: ['csv', 'xml', 'xls', 'xlsx'], md: '$mdiFileExcel' },
    { name: 'audio', ext: ['aif', 'iff', 'm3u', 'wav', 'mp3', 'webm'], md: '$mdiFileMusic' },
    { name: 'video', ext: ['flv', 'm4v', 'mov', 'mp4', 'wmv', 'avi'], md: '$mdiFileVideo' },
    { name: 'archive', ext: ['7z', 'zip', 'rar', 'gz'], md: '$mdiFileCloud' },
    { name: 'pdf', ext: ['pdf'], md: '$mdiFilePdfBox' },
    { name: 'ppt', ext: ['ppt', 'pptx'], md: '$mdiFilePowerpoint' },
  ],
};

export default Mimes;

export const docMimes = [
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/x-iwork-pages-sffpages',
  'application/rtf',
  'text/plain',
  'application/pdf',
  'text/csv',
  'application/xml',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];

export const uploadFileExtensions = Mimes.list
  .filter((mime) => ['image', 'doc', 'data', 'audio', 'archive', 'pdf', 'ppt'].includes(mime.name))
  .flatMap((mime) => mime.ext)
  .map((ext) => `.${ext}`);

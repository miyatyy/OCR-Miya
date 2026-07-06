const Tesseract = require("tesseract.js");

const imagePath = process.argv[2];

async function runOCR() {
  const result = await Tesseract.recognize(
    imagePath,
    "eng+ind"
  );

  console.log(result.data.text);
}

runOCR();
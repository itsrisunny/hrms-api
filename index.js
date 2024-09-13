// app.js
const express = require("express");
var cors = require("cors");
const bodyParser = require("body-parser");
const loginRouter = require("./routes/Login");
const app = express();

app.use(bodyParser.json());
app.use(cors());
app.get("/", (req, res) => {
  res.json({ message: "Welcome to HRMS API" });
});

app.use("/api", loginRouter);
const port = 5000;
app.listen(port, () => {
  console.log(`Server started on port ${port}`);
});

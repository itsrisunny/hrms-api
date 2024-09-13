const express = require("express");
const router = express.Router();
const {
  generateToken,
  verifyToken,
  comparePassword,
  hashPassword,
  authMiddleware,
} = require("../helper/auth");
const { getUserByUsername } = require("../services/login");

router.post("/login", async (req, res) => {
  const { username, password } = req.body;
  if (!username || !password) {
    return res
      .status(200)
      .json({ message: "Username and password are required" });
  }
  const user = await getUserByUsername(username);
  if (user.status !== 200) {
    return res.status(200).json({ message: user.message });
  }
  const isValidPassword = await comparePassword(password, user.Password);
  if (!isValidPassword) {
    return res.status(200).json({ message: "Invalid password" });
  }
  const token = generateToken(user);
  res.json({ token: token, status: 200 });
});
router.post("/token", async (req, res) => {
  const { username } = req.body;
  if (!username) {
    return res.status(200).json({ message: "Username is required" });
  }
  const user = await getUserByUsername(username);
  if (user.status === 200) {
    const token = generateToken(user);
    res.json({ token: token, status: 200 });
  } else {
    res.json({ token: null, status: 400 });
  }
});
router.post("/user-detail", authMiddleware, async (req, res) => {
  const { username } = req.body;
  if (!username) {
    return res.status(200).json({ message: "Username is required" });
  }
  const user = await getUserByUsername(username);
  if (user.status === 200) {
    return res
      .status(200)
      .json({ message: "Success", status: 200, data: user });
  } else {
    return res
      .status(200)
      .json({ message: "No record found!", status: 404, data: {} });
  }
});
module.exports = router;

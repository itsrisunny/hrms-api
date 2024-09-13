const jwt = require("jsonwebtoken");
const bcrypt = require("bcrypt");

const secretKey = "your_secret_key_here";
const expiresIn = "1h";

const generateToken = (user) => {
  const payload = { userId: user.EmployeeID, username: user.Email };
  return jwt.sign(payload, secretKey, { expiresIn });
};

const verifyToken = (token) => {
  try {
    const decoded = jwt.verify(token, secretKey);
    console.log(decoded);
    return decoded;
  } catch (err) {
    return null;
  }
};

const hashPassword = (password) => {
  return bcrypt.hash(password, 10);
};

const comparePassword = (password, hashedPassword) => {
  return bcrypt.compare(password, hashedPassword);
};

const authMiddleware = async (req, res, next) => {
  const token = req.header("Authorization");
  if (!token) {
    return res.status(401).json({ message: "Unauthorized" });
  }

  try {
    const decoded = jwt.verify(token.replace(/^Bearer /, ""), secretKey);
    req.user = decoded;
    next();
  } catch (error) {
    if (error.name === "TokenExpiredError") {
      return res.status(401).json({ message: "Token has expired" });
    } else if (error.name === "JsonWebTokenError") {
      return res.status(401).json({ message: "Invalid token format" });
    } else {
      return res.status(401).json({ message: "Invalid token" });
    }
  }
};

module.exports = {
  generateToken,
  verifyToken,
  hashPassword,
  comparePassword,
  authMiddleware,
};

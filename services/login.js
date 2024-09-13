const db = require("./../config/db");
const getUserByUsername = async (username) => {
  if (username === undefined) {
    return { message: "Username is required", status: 404 };
  }
  try {
    const rows = await db.query(
      `SELECT * FROM employees WHERE Email = '${username}'`
    );
    if (rows.length === 0) {
      return { message: "You are not registered with us.", status: 404 };
    }
    rows[0].status = 200;

    return rows[0];
  } catch (error) {
    return { message: error.message, status: 404 };
  }
};
module.exports = { getUserByUsername };

// db.js
const mysql = require("mysql2/promise");

const dbConfig = {
  host: "localhost",
  user: "root",
  password: "",
  database: "hrms_db",
};

/*const createUser = async (username, password) => {
  const hashedPassword = await hashPassword(password);
  await db.execute("INSERT INTO users (username, password) VALUES (?, ?)", [
    username,
    hashedPassword,
  ]);
};*/

async function query(sql, params) {
  const connection = await mysql.createConnection(dbConfig);
  try {
    const [results] = await connection.execute(sql, params);
    return results;
  } finally {
    await connection.end();
  }
}

module.exports = {
  query,
};

//module.exports = { db, createUser };

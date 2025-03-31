import express from 'express';
import createTables from './createTables';
import pool from './connection';
import Router from './routes/router';
import { insertEvents } from './db';
import cors from 'cors';
import path from 'path';
import swaggerUi from 'swagger-ui-express';
import { specs } from './swagger';

export const app = express();

app.use(cors({
    origin: ['http://localhost:5173', 'http://localhost:8888'], // Add MAMP's port
    methods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization'],
    credentials: true
}));

app.use(express.json());
app.use('/api', Router);

// Serve event images
const eventImagesPath = path.join(__dirname, './images');
app.use('/images', (req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*'); // Allow images to be accessed from anywhere
    next();
}, express.static(eventImagesPath));

// Serve wine images
const wineImagesPath = path.join(__dirname, './images/wineimages');
app.use('/images/wineimages', (req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*'); // Allow images to be accessed from anywhere
    next();
}, express.static(wineImagesPath));

// Add Swagger documentation route
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(specs));

pool.getConnection()
    .then(connection => {
        console.log('Connected to the MySQL server.');
        connection.release();
        createTables().then(() => {
            insertEvents(); // Ensure this is called after tables are created
        });
    })
    .catch(err => {
        console.error('Error connecting to the MySQL server:', err);
    });


const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Server is running on port ${port}!`));
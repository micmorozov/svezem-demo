#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 27 - Перевозка экскаваторов

/*
перевозти 2 экскаватора
*/

Fact -> "экскаватор";

S -> Fact interp(Category.Category_27);